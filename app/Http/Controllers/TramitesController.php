<?php

namespace App\Http\Controllers;

use App\Models\DocumentoPredio;
use App\Models\Predio;
use App\Models\tblDocumentoPersonal;
use App\Models\Tramite;
use Illuminate\View\View;

class TramitesController extends Controller
{
    public function indexTramites(): View
    {
        $tramites = Tramite::where('estatus_tramite', 1)
            ->with([
                'dependencia',
                'requisitos' => fn ($query) => $query->where('estatus_requisito', 1)->orderBy('nombre_requisito'),
            ])
            ->orderBy('nombre_tramite')
            ->get();

        $dependencias = $tramites
            ->pluck('dependencia')
            ->filter()
            ->unique('id_dependencia')
            ->sortBy('nombre_dependencia')
            ->values();

        return view('tramites.indexTramites', [
            'tramites' => $tramites,
            'dependencias' => $dependencias,
        ]);
    }

    public function iniciarTramite(Tramite $tramite): View
    {
        $tramite->load([
            'dependencia',
            'requisitos' => fn ($query) => $query->where('estatus_requisito', 1)->orderBy('nombre_requisito'),
        ]);

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

        $data = [
            'tramite' => $tramite,
            'documentosAprobados' => $documentosAprobados,
            'documentosPersonalesNombres' => $documentosPersonalesNombres,
            'documentosNoAprobadosNombres' => $documentosNoAprobadosNombres,
        ];

        // Si el trámite tiene cuenta_predial activa (1), cargar predios aprobados
        // para que el ciudadano seleccione el predio y se pre-llenen requisitos.
        if ($tramite->cuenta_predial) {
            $prediosAprobados = Predio::where('fk_usuario', auth()->id())
                ->where('estatus_predio', Predio::ESTATUS_APROBADO)
                ->with([
                    'documentos' => fn ($q) => $q
                        ->where('estatus_documento', DocumentoPredio::ESTATUS_APROBADO)
                        ->with('catalogoDocumento'),
                ])
                ->orderByDesc('id_predio')
                ->get();

            $data['prediosAprobados'] = $prediosAprobados;
            $data['esTramitePredial'] = true;
        }

        return view('tramites.iniciarTramite', $data);
    }
}
