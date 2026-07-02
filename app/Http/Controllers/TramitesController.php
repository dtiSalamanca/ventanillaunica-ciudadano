<?php

namespace App\Http\Controllers;

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
}
