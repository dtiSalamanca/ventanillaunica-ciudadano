<?php

namespace App\Http\Controllers;

use App\Models\catDocumentoPersonal;
use App\Models\tblDocumentoPersonal;
use Illuminate\View\View;

class PerfilesController extends Controller
{
    public function indexPerfiles(): View
    {
        $documentosCatalogo = tblDocumentoPersonal::where('estatus_documento', 1)
            ->orderBy('nombre_documento')
            ->get();

        $documentosCargados = catDocumentoPersonal::where('fk_usuario', auth()->id())
            ->get()
            ->keyBy('fk_documento_personal');

        $totalDocumentos = $documentosCatalogo->count();
        $documentosCompletados = $documentosCatalogo->filter(
            fn (tblDocumentoPersonal $documento) => $documentosCargados->has($documento->id_documento)
        )->count();

        return view('perfil.indexPerfil', [
            'documentosCatalogo' => $documentosCatalogo,
            'documentosCargados' => $documentosCargados,
            'totalDocumentos' => $totalDocumentos,
            'documentosCompletados' => $documentosCompletados,
        ]);
    }
}
