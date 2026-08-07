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
use Illuminate\Support\Str;
use Illuminate\View\View;

class TramitesController extends Controller
{
    public function indexTramites(): View
    {
        $tramites = Tramite::where('estatus_tramite', 1)
            ->with('dependencia')
            ->orderBy('nombre_tramite')
            ->get();

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

        $idsTramitesRequeridos = $prerequisitos->pluck('id_tramite');

        foreach ($prerequisitos as $prerequisito) {
            $completado = Solicitud::where('fk_usuario', $usuarioId)
                ->where('fk_tramite', $prerequisito->id_tramite)
                ->where('estatus_solicitud', 4) // 4 = Completado
                ->exists();

            if (! $completado) {
                $prerequisitosPendientes->push($prerequisito);
            }
        }

        // Nombres de tramites prerequisitos que el usuario ya completó
        $tramitesCompletadosNombres = collect();
        if ($idsTramitesRequeridos->isNotEmpty()) {
            $tramitesCompletadosNombres = Solicitud::where('fk_usuario', $usuarioId)
                ->whereIn('fk_tramite', $idsTramitesRequeridos)
                ->where('estatus_solicitud', 4)
                ->with('tramite')
                ->get()
                ->map(fn ($s) => trim($s->tramite?->nombre_tramite ?? ''))
                ->filter()
                ->values();
        }

        $documentosAprobados = tblDocumentoPersonal::where('fk_usuario', auth()->id())
            ->where('estatus_documento', tblDocumentoPersonal::ESTATUS_APROBADO)
            ->with('catalogoDocumento')
            ->orderByDesc('fecha_registro')
            ->get();

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
        ];

        // Si el trámite tiene cuenta_predial activa (1), cargar predios aprobados
        // para que el ciudadano seleccione el predio y se pre-llenen requisitos.
        if ($tramite->cuenta_predial) {
            // IDs de predios que ya tienen una solicitud pendiente para este trámite
            $prediosConSolicitudPendiente = Solicitud::where('fk_tramite', $tramite->id_tramite)
                ->where('fk_usuario', auth()->id())
                ->where('estatus_solicitud', 0) // 0 = Pendiente
                ->whereNotNull('fk_predio')
                ->pluck('fk_predio')
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
                ->get();

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
            $completado = Solicitud::where('fk_usuario', $usuarioId)
                ->where('fk_tramite', $prerequisito->id_tramite)
                ->where('estatus_solicitud', 4) // 4 = Completado
                ->exists();

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

        // Nombres de tramites prerequisitos completados por el usuario
        $tramitesCompletadosNombres = collect();
        if ($idsTramitesRequeridos->isNotEmpty()) {
            $tramitesCompletadosNombres = Solicitud::where('fk_usuario', $usuarioId)
                ->whereIn('fk_tramite', $idsTramitesRequeridos)
                ->where('estatus_solicitud', 4)
                ->with('tramite')
                ->get()
                ->map(fn ($s) => trim($s->tramite?->nombre_tramite ?? ''))
                ->filter()
                ->values();
        }

        // Validar que el predio no tenga ya una solicitud pendiente para este trámite
        if ($tramite->cuenta_predial && $request->filled('predio_id')) {
            $yaTieneSolicitud = Solicitud::where('fk_tramite', $tramite->id_tramite)
                ->where('fk_usuario', auth()->id())
                ->where('fk_predio', $request->integer('predio_id'))
                ->where('estatus_solicitud', 0)
                ->exists();

            if ($yaTieneSolicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este predio ya tiene una solicitud pendiente para este trámite.',
                ], 422);
            }
        }

        $user = auth()->user();

        // Obtener documentos personales aprobados del usuario
        $documentosAprobados = tblDocumentoPersonal::where('fk_usuario', $user->id)
            ->where('estatus_documento', tblDocumentoPersonal::ESTATUS_APROBADO)
            ->with('catalogoDocumento')
            ->get();

        // Mapa: nombre_documento (normalizado) => id del documento personal
        $documentosMap = $documentosAprobados
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

            $documentosPredioMap = $documentosPredio
                ->mapWithKeys(fn ($doc) => [
                    mb_strtolower(trim($doc->catalogoDocumento?->nombre_documento ?? '')) => $doc->id_documento_predio,
                ])
                ->filter()
                ->toArray();
        }

        // Hacer matching requisito vs documento personal/predio
        $requisitosCubiertos = [];
        $todosCubiertos = true;

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
                break;
            }

            $requisitosCubiertos[] = [
                'requisito' => $requisito,
                'documento_id' => $documentoId,
                'tipo' => $tipoDocumento,
            ];
        }

        if (! $todosCubiertos) {
            return response()->json([
                'success' => false,
                'message' => 'No todos los requisitos están cumplidos. Revisa los documentos en tu perfil.',
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
            'predio',
        ])
            ->where('fk_usuario', auth()->id())
            ->orderByDesc('fecha_solicitud')
            ->get();

        return view('tramites.misTramites', [
            'solicitudes' => $solicitudes,
        ]);
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
