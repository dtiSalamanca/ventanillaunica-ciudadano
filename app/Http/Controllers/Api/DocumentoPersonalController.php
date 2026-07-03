<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\catDocumentoPersonal;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentoPersonalController extends Controller
{
    /**
     * Devuelve el archivo del documento personal para que el panel
     * administrador lo visualice inline en el visor del navegador.
     */
    public function mostrarArchivo(catDocumentoPersonal $registroDocumento): BinaryFileResponse
    {
        $disk = Storage::disk('local');
        $ruta = $this->resolverRutaArchivo($registroDocumento, $disk);

        abort_if($ruta === null, 404, 'El archivo no existe.');

        $nombreDescarga = str_replace(' ', '-', $registroDocumento->catalogoDocumento->nombre_documento).'.pdf';

        return response()->file(
            $disk->path($ruta),
            [
                'Content-Disposition' => 'inline; filename="'.$nombreDescarga.'"',
            ],
        );
    }

    /**
     * Resuelve la ruta del archivo a servir: usa la columna `ruta_archivo`
     * si está disponible; si no (registros antiguos), elige el archivo más
     * reciente del directorio por fecha de modificación.
     *
     * @return string|null Ruta relativa en el disco `local`, o null si no existe.
     */
    private function resolverRutaArchivo(catDocumentoPersonal $registroDocumento, $disk): ?string
    {
        if ($registroDocumento->ruta_archivo && $disk->exists($registroDocumento->ruta_archivo)) {
            return $registroDocumento->ruta_archivo;
        }

        $archivos = $disk->files($registroDocumento->directorioArchivo());

        if (empty($archivos)) {
            return null;
        }

        return collect($archivos)
            ->sortByDesc(fn (string $archivo) => $disk->lastModified($archivo))
            ->first();
    }
}
