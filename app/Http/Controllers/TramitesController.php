<?php

namespace App\Http\Controllers;

use App\Models\DocumentoPredio;
use App\Models\DocumentoTramite;
use App\Models\Predio;
use App\Models\Solicitud;
use App\Models\tblDocumentoPersonal;
use App\Models\Tramite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TramitesController extends Controller
{
    public function indexTramites(): View
    {
        $tramites = Tramite::where('estatus_tramite', 1)
            ->with('dependencia')
            ->orderBy('nombre_tramite')
            ->get();

        // Ocultar del catálogo los trámites NO prediales que el ciudadano ya tiene
        // completados y vigentes (estadoMostrado = 5), porque no podrá volver a
        // solicitarlos. Los trámites prediales (cuenta_predial) SIEMPRE se muestran,
        // ya que pueden volver a solicitarse por cada predio. Los mensajes de bloqueo
        // de la vista "iniciar trámite" se conservan por si este flujo cambia.
        if (auth()->check()) {
            // Solo se ocultan los trámites con una solicitud completada cuya
            // vigencia NO ha vencido (estadoMostrado = 5). Si la solicitud está
            // expirada (6), el trámite vuelve a aparecer y puede solicitarse de nuevo.
            $tramitesCompletados = Solicitud::where('fk_usuario', auth()->id())
                ->whereIn('fk_tramite', $tramites->pluck('id_tramite'))
                ->with(['ordenPago', 'tramite', 'resolucion'])
                ->get()
                ->filter(fn ($solicitud) => $solicitud->estadoMostrado() === 5)
                ->pluck('fk_tramite')
                ->unique()
                ->values();

            $tramites = $tramites
                ->reject(fn ($tramite) => ! $tramite->cuenta_predial && $tramitesCompletados->contains($tramite->id_tramite))
                ->values();
        }

        $dependencias = $tramites
            ->pluck('dependencia')
            ->filter()
            ->unique('id_dependencia')
            ->sortBy('nombre_dependencia')
            ->values();

        $requisitosPorTramite = Tramite::mapaRequisitosVisibles($tramites);

        return view('tramites.indexTramites', [
            'tramites' => $tramites,
            'dependencias' => $dependencias,
            'requisitosPorTramite' => $requisitosPorTramite,
        ]);
    }

    public function iniciarTramite(Tramite $tramite): View
    {
        $tramite->load([
            'dependencia',
            'tramitesRequeridos',
        ]);

        // ── Validar prerequisitos del trámite ──
        $prerequisitos = $tramite->tramitesRequeridos;
        $prerequisitosPendientes = collect();
        $usuarioId = auth()->id();

        // ── Bloqueo por solicitud en proceso o completada ──
        // El estado efectivo replica la lógica visual de "Mis trámites": una
        // orden de pago con folio (ya pagada) se muestra como Completado (4) y
        // sin folio como Por pagar (3).
        // Regla de negocio: solo se puede volver a iniciar un trámite cuando la
        // solicitud anterior fue rechazada (2). En los prediales el bloqueo se
        // resuelve por predio en el selector.
        $solicitudesDelTramite = Solicitud::where('fk_usuario', $usuarioId)
            ->where('fk_tramite', $tramite->id_tramite)
            ->with(['ordenPago', 'tramite', 'resolucion'])
            ->get();

        $tieneSolicitudEnProceso = false;
        $tieneSolicitudCompletada = false;

        if (! $tramite->cuenta_predial) {
            foreach ($solicitudesDelTramite as $solicitud) {
                // El estado efectivo (0-6) considera el folio y la vigencia: si la
                // solicitud completada ya venció (6), no bloquea ni cuenta como completada.
                $estadoMostrado = $solicitud->estadoMostrado();

                if (in_array($estadoMostrado, [0, 1, 3], true)) {
                    $tieneSolicitudEnProceso = true;
                } elseif ($estadoMostrado === 5) {
                    $tieneSolicitudCompletada = true;
                }
            }
        }

        $idsTramitesRequeridos = $prerequisitos->pluck('id_tramite');

        foreach ($prerequisitos as $prerequisito) {
            // El prerequisito se considera cumplido solo si hay una solicitud
            // completada y vigente (estadoMostrado = 5). Si está expirada (6),
            // ya no es válida y debe volver a tramitarse.
            $completado = Solicitud::where('fk_usuario', $usuarioId)
                ->where('fk_tramite', $prerequisito->id_tramite)
                ->with(['ordenPago', 'tramite', 'resolucion'])
                ->get()
                ->contains(fn ($solicitud) => $solicitud->estadoMostrado() === 5);

            if (! $completado) {
                $prerequisitosPendientes->push($prerequisito);
            }
        }

        // Nombres de tramites prerequisitos que el usuario ya completó y están vigentes
        $tramitesCompletadosNombres = collect();
        if ($idsTramitesRequeridos->isNotEmpty()) {
            $tramitesCompletadosNombres = Solicitud::where('fk_usuario', $usuarioId)
                ->whereIn('fk_tramite', $idsTramitesRequeridos)
                ->with(['ordenPago', 'tramite', 'resolucion'])
                ->get()
                ->filter(fn ($solicitud) => $solicitud->estadoMostrado() === 5)
                ->map(fn ($solicitud) => trim($solicitud->tramite?->nombre_tramite ?? ''))
                ->filter()
                ->values();
        }

        $documentosAprobados = tblDocumentoPersonal::where('fk_usuario', auth()->id())
            ->where('estatus_documento', tblDocumentoPersonal::ESTATUS_APROBADO)
            ->with('catalogoDocumento')
            ->orderByDesc('fecha_registro')
            ->get();

        // Un documento aprobado que está por vencer (a 3 días o menos) o ya
        // vencido deja de ser válido para iniciar un trámite: exige recargarlo
        // desde el perfil. Se excluye de los documentos "aprobados" vigentes.
        $documentosAprobados = $documentosAprobados->reject(
            fn ($doc) => $doc->estaPorVencer(3)
        );

        // Todos los documentos del usuario (sin importar estatus), para saber
        // cuáles existen pero no están aprobados y sugerir ir al perfil.
        $todosDocumentos = tblDocumentoPersonal::where('fk_usuario', auth()->id())
            ->with('catalogoDocumento')
            ->get();

        // Nombres normalizados de documentos personales aprobados
        $documentosPersonalesNombres = $documentosAprobados
            ->map(fn ($doc) => mb_strtolower(trim($doc->catalogoDocumento?->nombre_documento ?? '')))
            ->filter()
            ->values()
            ->toArray();

        // Nombres de documentos que existen pero NO están aprobados (pendientes o rechazados)
        $documentosNoAprobadosNombres = $todosDocumentos
            ->reject(fn ($doc) => $doc->estatus_documento === tblDocumentoPersonal::ESTATUS_APROBADO)
            ->map(fn ($doc) => mb_strtolower(trim($doc->catalogoDocumento?->nombre_documento ?? '')))
            ->filter()
            ->values()
            ->toArray();

        // Crear requisitos virtuales para los trámites prerequisitos que el usuario ya completó
        // para que se muestren visualmente en la lista de requisitos
        $requisitosVirtuales = collect();
        if ($prerequisitosPendientes->isEmpty() && $tramitesCompletadosNombres->isNotEmpty()) {
            $requisitosVirtuales = $tramitesCompletadosNombres->map(fn ($nombre) => (object) [
                'id_requisito' => 'prereq_'.mb_strtolower(Str::slug($nombre)),
                'nombre_requisito' => $nombre,
                'es_virtual' => true,
            ]);
        }

        $todosRequisitos = $requisitosVirtuales->merge($tramite->requisitosVisibles());
        $totalRequisitos = $todosRequisitos->count();

        $data = [
            'tramite' => $tramite,
            'todosRequisitos' => $todosRequisitos,
            'totalRequisitos' => $totalRequisitos,
            'documentosAprobados' => $documentosAprobados,
            'documentosPersonalesNombres' => $documentosPersonalesNombres,
            'documentosNoAprobadosNombres' => $documentosNoAprobadosNombres,
            'prerequisitosPendientes' => $prerequisitosPendientes,
            'tramitesCompletadosNombres' => $tramitesCompletadosNombres,
            'tieneSolicitudEnProceso' => $tieneSolicitudEnProceso,
            'tieneSolicitudCompletada' => $tieneSolicitudCompletada,
        ];

        // Si el trámite tiene cuenta_predial activa (1), cargar predios aprobados
        // para que el ciudadano seleccione el predio y se pre-llenen requisitos.
        if ($tramite->cuenta_predial) {
            // Predios que NO deben aparecer en el selector: los que ya tienen una
            // solicitud de ESTE trámite en proceso (0,1,3) o completada y vigente (5).
            // Los predios con solicitud rechazada (2) o expirada (6) sí pueden usarse.
            $prediosConSolicitudPendiente = Solicitud::where('fk_tramite', $tramite->id_tramite)
                ->where('fk_usuario', auth()->id())
                ->whereNotNull('fk_predio')
                ->with(['ordenPago', 'tramite', 'resolucion'])
                ->get()
                ->filter(fn ($solicitud) => in_array($solicitud->estadoMostrado(), [0, 1, 3, 5], true))
                ->pluck('fk_predio')
                ->unique()
                ->values()
                ->toArray();

            $prediosAprobados = Predio::where('fk_usuario', auth()->id())
                ->where('estatus_predio', Predio::ESTATUS_APROBADO)
                ->whereNotIn('id_predio', $prediosConSolicitudPendiente)
                ->with([
                    'documentos' => fn ($q) => $q
                        ->where('estatus_documento', DocumentoPredio::ESTATUS_APROBADO)
                        ->with('catalogoDocumento'),
                ])
                ->orderByDesc('id_predio')
                ->get()
                ->each(function (Predio $predio) {
                    // Los documentos del predio por vencer o vencidos tampoco
                    // cuentan como válidos para iniciar el trámite.
                    $predio->setRelation(
                        'documentos',
                        $predio->documentos->reject(fn ($doc) => $doc->estaPorVencer(3))
                    );
                });

            $data['prediosAprobados'] = $prediosAprobados;
            $data['esTramitePredial'] = true;

            // Verificar si el usuario tiene predios pero todos están bloqueados por solicitudes pendientes
            $totalPrediosAprobados = Predio::where('fk_usuario', auth()->id())
                ->where('estatus_predio', Predio::ESTATUS_APROBADO)
                ->count();

            $data['tienePrediosBloqueados'] = $totalPrediosAprobados > 0 && $prediosAprobados->isEmpty();
        }

        return view('tramites.iniciarTramite', $data);
    }

    /**
     * Consulta los adeudos de la cuenta predial de un predio del ciudadano
     * autenticado contra el sistema de recibo predial.
     */
    public function consultarAdeudoPredio(Predio $predio): JsonResponse
    {
        abort_unless($predio->fk_usuario === auth()->id(), 403);

        $resultado = $predio->consultarAdeudo();

        return response()->json([
            'success' => true,
            'clave_predio' => $predio->clave_predio,
            'estado' => $resultado['estado'],
            'mensaje' => $resultado['mensaje'],
            'adeudos' => $resultado['adeudos'] ?? 0,
        ]);
    }

    public function enviarSolicitud(Request $request): JsonResponse
    {
        $request->validate([
            'tramite_id' => ['required', 'integer', 'exists:cat_tramites,id_tramite'],
            'predio_id' => ['nullable', 'integer', 'exists:tbl_predios,id_predio'],
        ]);

        $tramite = Tramite::with(['tramitesRequeridos'])->findOrFail($request->integer('tramite_id'));

        // ── Validar prerequisitos del trámite ──
        $usuarioId = auth()->id();
        $prerequisitosPendientes = collect();

        $prerequisitos = $tramite->tramitesRequeridos;
        $idsTramitesRequeridos = $prerequisitos->pluck('id_tramite');

        foreach ($prerequisitos as $prerequisito) {
            // El prerequisito se considera cumplido solo si hay una solicitud
            // completada y vigente (estadoMostrado = 5). Si está expirada (6),
            // ya no es válida y debe volver a tramitarse.
            $completado = Solicitud::where('fk_usuario', $usuarioId)
                ->where('fk_tramite', $prerequisito->id_tramite)
                ->with(['ordenPago', 'tramite', 'resolucion'])
                ->get()
                ->contains(fn ($solicitud) => $solicitud->estadoMostrado() === 5);

            if (! $completado) {
                $prerequisitosPendientes->push($prerequisito);
            }
        }

        if ($prerequisitosPendientes->isNotEmpty()) {
            $nombres = $prerequisitosPendientes->pluck('nombre_tramite')->implode('", "');

            return response()->json([
                'success' => false,
                'message' => "Para solicitar **{$tramite->nombre_tramite}**, primero debes completar el trámite: \"{$nombres}\".",
            ], 422);
        }

        // Nombres de tramites prerequisitos completados por el usuario y vigentes
        $tramitesCompletadosNombres = collect();
        if ($idsTramitesRequeridos->isNotEmpty()) {
            $tramitesCompletadosNombres = Solicitud::where('fk_usuario', $usuarioId)
                ->whereIn('fk_tramite', $idsTramitesRequeridos)
                ->with(['ordenPago', 'tramite', 'resolucion'])
                ->get()
                ->filter(fn ($solicitud) => $solicitud->estadoMostrado() === 5)
                ->map(fn ($solicitud) => trim($solicitud->tramite?->nombre_tramite ?? ''))
                ->filter()
                ->values();
        }

        // Validar que el ciudadano no tenga ya una solicitud en proceso de este trámite
        // (Pendiente=0, Turnado=1, Por pagar=3 sin folio). Una solicitud completada (4)
        // o expirada (6) NO cuenta como "en proceso". En trámites prediales la validación
        // es por predio; en los demás, cualquier solicitud activa del trámite lo bloquea.
        $yaTieneSolicitudEnProceso = Solicitud::where('fk_tramite', $tramite->id_tramite)
            ->where('fk_usuario', auth()->id())
            ->with(['ordenPago', 'tramite', 'resolucion'])
            ->get()
            ->contains(function ($solicitud) use ($request) {
                if (! in_array($solicitud->estadoMostrado(), [0, 1, 3], true)) {
                    return false;
                }

                return $request->filled('predio_id')
                    ? $solicitud->fk_predio === $request->integer('predio_id')
                    : $solicitud->fk_predio === null;
            });

        if ($yaTieneSolicitudEnProceso) {
            $mensaje = $request->filled('predio_id')
                ? 'Este predio ya tiene una solicitud en proceso para este trámite.'
                : 'Ya tienes una solicitud de este trámite en proceso. Revisa tus trámites.';

            return response()->json([
                'success' => false,
                'message' => $mensaje,
            ], 422);
        }

        // Validar que el ciudadano no tenga ya un trámite completado de este tipo
        // (estatus 4 o por pagar ya pagado con folio). Un trámite solo puede
        // solicitarse de nuevo si la solicitud anterior fue rechazada o su vigencia
        // venció (expirada). En trámites prediales el bloqueo es por predio.
        $yaTieneSolicitudCompletada = Solicitud::where('fk_tramite', $tramite->id_tramite)
            ->where('fk_usuario', auth()->id())
            ->with(['ordenPago', 'tramite', 'resolucion'])
            ->get()
            ->contains(function ($solicitud) use ($request) {
                // Una solicitud expirada (estadoMostrado = 6) ya no se considera completada.
                if ($solicitud->estadoMostrado() !== 5) {
                    return false;
                }

                return $request->filled('predio_id')
                    ? $solicitud->fk_predio === $request->integer('predio_id')
                    : true;
            });

        if ($yaTieneSolicitudCompletada) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes un trámite completado de este tipo y no puede solicitarse de nuevo.',
            ], 422);
        }

        // Validar adeudos de la cuenta predial contra el sistema de recibo predial
        if ($tramite->cuenta_predial && $request->filled('predio_id')) {
            $predio = Predio::where('id_predio', $request->integer('predio_id'))
                ->where('fk_usuario', auth()->id())
                ->first();

            if (! $predio) {
                return response()->json([
                    'success' => false,
                    'message' => 'El predio seleccionado no es válido.',
                ], 422);
            }

            $resultadoAdeudo = $predio->consultarAdeudo();

            if ($resultadoAdeudo['estado'] === 'adeudos') {
                return response()->json([
                    'success' => false,
                    'message' => "No puedes realizar el trámite \"{$tramite->nombre_tramite}\" porque la cuenta predial {$predio->clave_predio} tiene adeudos pendientes.",
                ], 422);
            }

            if ($resultadoAdeudo['estado'] === 'no_encontrado') {
                return response()->json([
                    'success' => false,
                    'message' => "No se encontraron registros de la cuenta predial {$predio->clave_predio}. Verifica la clave de tu predio.",
                ], 422);
            }
        }

        $user = auth()->user();

        // Obtener documentos personales aprobados del usuario
        $documentosAprobados = tblDocumentoPersonal::where('fk_usuario', $user->id)
            ->where('estatus_documento', tblDocumentoPersonal::ESTATUS_APROBADO)
            ->with('catalogoDocumento')
            ->get();

        // Un documento vencido o por vencer (a 3 días o menos) deja de ser
        // válido para iniciar un trámite: exige recargarlo en el perfil.
        $documentosValidos = $documentosAprobados->reject(
            fn ($doc) => $doc->estaPorVencer(3)
        );

        // Nombres normalizados de documentos vencidos o por vencer (para dar
        // un aviso específico al ciudadano).
        $documentosInvalidadosNombres = $documentosAprobados
            ->filter(fn ($doc) => $doc->estaPorVencer(3))
            ->map(fn ($doc) => mb_strtolower(trim($doc->catalogoDocumento?->nombre_documento ?? '')))
            ->filter()
            ->values()
            ->toArray();

        // Mapa: nombre_documento (normalizado) => id del documento personal
        $documentosMap = $documentosValidos
            ->mapWithKeys(fn ($doc) => [
                mb_strtolower(trim($doc->catalogoDocumento?->nombre_documento ?? '')) => $doc->id_documento,
            ])
            ->filter()
            ->toArray();

        // Si es trámite predial, también considerar documentos del predio seleccionado
        $documentosPredioMap = [];
        if ($tramite->cuenta_predial && $request->filled('predio_id')) {
            $documentosPredio = DocumentoPredio::where('fk_predio', $request->integer('predio_id'))
                ->where('estatus_documento', DocumentoPredio::ESTATUS_APROBADO)
                ->with('catalogoDocumento')
                ->get();

            $documentosInvalidadosNombres = array_merge(
                $documentosInvalidadosNombres,
                $documentosPredio
                    ->filter(fn ($doc) => $doc->estaPorVencer(3))
                    ->map(fn ($doc) => mb_strtolower(trim($doc->catalogoDocumento?->nombre_documento ?? '')))
                    ->filter()
                    ->values()
                    ->toArray()
            );

            $documentosPredioMap = $documentosPredio
                ->reject(fn ($doc) => $doc->estaPorVencer(3))
                ->mapWithKeys(fn ($doc) => [
                    mb_strtolower(trim($doc->catalogoDocumento?->nombre_documento ?? '')) => $doc->id_documento_predio,
                ])
                ->filter()
                ->toArray();
        }

        // Hacer matching requisito vs documento personal/predio
        $requisitosCubiertos = [];
        $todosCubiertos = true;
        $documentoBloqueado = null;

        foreach ($tramite->requisitosVisibles() as $requisito) {
            $nombreRequisito = mb_strtolower(trim($requisito->nombre_requisito));
            $documentoId = null;
            $tipoDocumento = null;

            // Buscar primero en documentos personales aprobados
            foreach ($documentosMap as $nombreDoc => $idDoc) {
                if (
                    $nombreDoc === $nombreRequisito ||
                    str_contains($nombreRequisito, $nombreDoc) ||
                    str_contains($nombreDoc, $nombreRequisito)
                ) {
                    $documentoId = $idDoc;
                    $tipoDocumento = 'personal';
                    break;
                }
            }

            // Si no se encontró en personales y hay predio, buscar en documentos del predio
            if ($documentoId === null && ! empty($documentosPredioMap)) {
                foreach ($documentosPredioMap as $nombreDoc => $idDoc) {
                    if (
                        $nombreDoc === $nombreRequisito ||
                        str_contains($nombreRequisito, $nombreDoc) ||
                        str_contains($nombreDoc, $nombreRequisito)
                    ) {
                        $documentoId = $idDoc;
                        $tipoDocumento = 'predio';
                        break;
                    }
                }
            }

            // Si no se encontró en documentos, verificar si un trámite prerequisito completado lo cubre
            if ($documentoId === null && $tramitesCompletadosNombres->isNotEmpty()) {
                foreach ($tramitesCompletadosNombres as $nombreTramite) {
                    $nombreTramiteLower = mb_strtolower(trim($nombreTramite));
                    if (
                        $nombreTramiteLower === $nombreRequisito ||
                        str_contains($nombreRequisito, $nombreTramiteLower) ||
                        str_contains($nombreTramiteLower, $nombreRequisito)
                    ) {
                        $documentoId = true;
                        $tipoDocumento = 'tramite';
                        break;
                    }
                }
            }

            if ($documentoId === null) {
                $todosCubiertos = false;

                // Si el requisito lo cubre un documento vencido o por vencer,
                // avisar al ciudadano para que lo recargue desde su perfil.
                foreach ($documentosInvalidadosNombres as $nombreDoc) {
                    if (
                        $nombreDoc === $nombreRequisito ||
                        str_contains($nombreRequisito, $nombreDoc) ||
                        str_contains($nombreDoc, $nombreRequisito)
                    ) {
                        $documentoBloqueado = $requisito->nombre_requisito;
                        break;
                    }
                }

                break;
            }

            $requisitosCubiertos[] = [
                'requisito' => $requisito,
                'documento_id' => $documentoId,
                'tipo' => $tipoDocumento,
            ];
        }

        if (! $todosCubiertos) {
            $message = $documentoBloqueado
                ? "El documento \"{$documentoBloqueado}\" está vencido o por vencer. Recárgalo desde tu perfil para poder continuar."
                : 'No todos los requisitos están cumplidos. Revisa los documentos en tu perfil.';

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        // Crear la solicitud en una transacción
        try {
            $solicitud = DB::transaction(function () use ($user, $tramite, $request, $requisitosCubiertos) {
                $solicitud = Solicitud::create([
                    'fk_usuario' => $user->id,
                    'fk_tramite' => $tramite->id_tramite,
                    'fk_predio' => $request->filled('predio_id') ? $request->integer('predio_id') : null,
                    'fecha_solicitud' => now(),
                    'estatus_solicitud' => 0, // 0 = Pendiente
                ]);

                // Registrar cada requisito cubierto en tbl_documentos_tramites
                foreach ($requisitosCubiertos as $item) {
                    $data = [
                        'fk_requisito' => $this->idNumericoRequisito($item['requisito']),
                        'fk_solicitud' => $solicitud->id_solicitud,
                    ];

                    if ($item['tipo'] === 'personal') {
                        $data['fk_documento_personal'] = $item['documento_id'];
                    }

                    DocumentoTramite::create($data);
                }

                return $solicitud;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al enviar la solicitud. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada correctamente. Un administrador revisará tu trámite.',
            'solicitud_id' => $solicitud->id_solicitud,
        ]);
    }

    public function misTramites(): View
    {
        $solicitudes = Solicitud::with([
            'tramite.dependencia',
            'ordenPago',
            'predio',
            'resolucion',
        ])
            ->where('fk_usuario', auth()->id())
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('tramites.misTramites', [
            'solicitudes' => $solicitudes,
        ]);
    }

    /**
     * Muestra o descarga el resolutivo asociado a una solicitud del ciudadano
     * autenticado.
     */
    public function descargarResolutivo(Solicitud $solicitud): BinaryFileResponse
    {
        abort_if($solicitud->fk_usuario !== auth()->id(), 403);

        $resolucion = $solicitud->resolucion;

        abort_if($resolucion === null || blank($resolucion->documento_resolucion), 404, 'El resolutivo aún no está disponible.');

        $disk = Storage::disk('local');
        $ruta = $resolucion->documento_resolucion;
        $nombreArchivo = basename($ruta);

        if ($disk->exists($ruta)) {
            return response()->file($disk->path($ruta))
                ->setContentDisposition('inline', $nombreArchivo);
        }

        // En el entorno local ambos proyectos comparten la misma base de datos y
        // los resolutivos se guardan en el storage del proyecto administrador.
        $rutaAdmin = dirname(base_path()).'/ventanillaunica-administrador/storage/app/private/'.$ruta;

        abort_if(! is_file($rutaAdmin), 404, 'El archivo del resolutivo no existe.');

        return response()->file($rutaAdmin)
            ->setContentDisposition('inline', $nombreArchivo);
    }

    /**
     * Extrae el identificador numérico de un requisito visible para guardarlo en
     * tbl_documentos_tramites. Los requisitos de documento usan ids compuestos
     * ("personal_2", "predio_5"); los requisitos tradicionales usan entero directo.
     */
    private function idNumericoRequisito(object $requisito): int
    {
        if (is_int($requisito->id_requisito)) {
            return $requisito->id_requisito;
        }

        $partes = explode('_', (string) $requisito->id_requisito);

        return (int) end($partes);
    }
}
