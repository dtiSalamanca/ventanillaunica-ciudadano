<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentoPredio;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoPredioController extends Controller
{
    /**
     * Devuelve el archivo del documento de predio para que el panel
     * administrador lo visualice inline en el visor del navegador.
     */
    public function mostrarArchivo(DocumentoPredio $registroDocumento): BinaryFileResponse
    {
        $disk = Storage::disk('local');

        abort_if(! $disk->exists($registroDocumento->ruta_documento), 404, 'El archivo no existe.');

        $nombreDescarga = str_replace(' ', '-', $registroDocumento->catalogoDocumento->nombre_documento).'.pdf';

        return response()->file(
            $disk->path($registroDocumento->ruta_documento),
            [
                'Content-Disposition' => 'inline; filename="'.$nombreDescarga.'"',
            ],
        );
    }
}
