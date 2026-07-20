<?php

namespace App\Http\Controllers;

use App\Models\catDocumentoPersonal;
use App\Models\catDocumentoPredio;
use App\Models\Predio;
use App\Models\tblDocumentoPersonal;
use Illuminate\Contracts\Support\Renderable;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        $documentosCatalogo = catDocumentoPersonal::where('estatus_documento', 1)
            ->orderBy('nombre_documento')
            ->get();

        $documentosCargados = tblDocumentoPersonal::where('fk_usuario', auth()->id())
            ->get()
            ->keyBy('fk_documento_personal');

        $totalDocumentos = $documentosCatalogo->count();
        $documentosCompletados = $documentosCatalogo->filter(
            fn (catDocumentoPersonal $documento) => $documentosCargados->has($documento->id_documento)
        )->count();

        $catalogoPredios = catDocumentoPredio::where('estatus_documento', 1)
            ->orderBy('nombre_documento')
            ->get();

        $predios = Predio::where('fk_usuario', auth()->id())
            ->with('documentos')
            ->orderByDesc('id_predio')
            ->get();

        $totalDocumentosPrediosGlobal = $predios->count() * $catalogoPredios->count();
        $documentosPrediosCompletadosGlobal = 0;
        foreach ($predios as $predio) {
            $documentosPredioCargados = $predio->documentos->keyBy('fk_cat_documento_predio');
            $documentosPrediosCompletadosGlobal += $catalogoPredios
                ->filter(fn ($doc) => $documentosPredioCargados->has($doc->id_documento_predio))
                ->count();
        }

        return view('home', [
            'documentosCatalogo' => $documentosCatalogo,
            'documentosCargados' => $documentosCargados,
            'totalDocumentos' => $totalDocumentos,
            'documentosCompletados' => $documentosCompletados,
            'catalogoPredios' => $catalogoPredios,
            'predios' => $predios,
            'totalDocumentosPrediosGlobal' => $totalDocumentosPrediosGlobal,
            'documentosPrediosCompletadosGlobal' => $documentosPrediosCompletadosGlobal,
        ]);
    }
}
