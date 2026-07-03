<?php

namespace App\Http\Controllers;

use App\Models\catDocumentoPersonal;
use App\Models\tblDocumentoPersonal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function estatusDocumentos(): JsonResponse
    {
        $documentos = catDocumentoPersonal::where('fk_usuario', auth()->id())
            ->get()
            ->map(fn (catDocumentoPersonal $documento) => [
                'fk_documento_personal' => $documento->fk_documento_personal,
                'estatus' => $documento->estatus_documento,
                'fecha_registro' => $documento->fecha_registro->format('d/m/Y'),
                'url_descargar' => route('perfiles.documentos.descargar', $documento->id_documento),
                'url_subir' => route('perfiles.documentos.subir', $documento->fk_documento_personal),
            ]);

        return response()->json(['documentos' => $documentos]);
    }

    public function subirDocumento(Request $request, tblDocumentoPersonal $catalogoDocumento): RedirectResponse|JsonResponse
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.file' => 'El archivo no es válido.',
            'archivo.mimes' => 'Solo se permiten archivos PDF.',
            'archivo.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        $registroExistente = catDocumentoPersonal::where('fk_usuario', auth()->id())
            ->where('fk_documento_personal', $catalogoDocumento->id_documento)
            ->first();

        // Eliminar archivo anterior si existe
        if ($registroExistente) {
            $archivosAnteriores = Storage::disk('local')->files($registroExistente->directorioArchivo());
            Storage::disk('local')->delete($archivosAnteriores);
        }

        $directorio = 'documentos_personales/'.auth()->id()."/{$catalogoDocumento->id_documento}";
        $rutaArchivo = $request->file('archivo')->store($directorio, 'local');

        if ($registroExistente) {
            $registroExistente->update([
                'fecha_registro' => now(),
                'estatus_documento' => catDocumentoPersonal::ESTATUS_EN_REVISION,
                'ruta_archivo' => $rutaArchivo,
            ]);
        } else {
            catDocumentoPersonal::create([
                'fk_usuario' => auth()->id(),
                'fk_documento_personal' => $catalogoDocumento->id_documento,
                'fecha_registro' => now(),
                'estatus_documento' => catDocumentoPersonal::ESTATUS_EN_REVISION,
                'ruta_archivo' => $rutaArchivo,
            ]);
        }

        $registro = $registroExistente ?? catDocumentoPersonal::where('fk_usuario', auth()->id())
            ->where('fk_documento_personal', $catalogoDocumento->id_documento)
            ->first();

        if ($request->expectsJson()) {
            return response()->json([
                'id_registro' => $registro->id_documento,
                'fecha_registro' => $registro->fecha_registro->format('d/m/Y'),
                'estatus' => $registro->estatus_documento,
                'url_descargar' => route('perfiles.documentos.descargar', $registro->id_documento),
                'url_subir' => route('perfiles.documentos.subir', $catalogoDocumento->id_documento),
            ]);
        }

        return redirect()->route('indexPerfiles')
            ->with('success', "El documento «{$catalogoDocumento->nombre_documento}» se envió para revisión correctamente.");
    }

    public function descargarDocumento(catDocumentoPersonal $registroDocumento): BinaryFileResponse
    {
        abort_if($registroDocumento->fk_usuario !== auth()->id(), 403);

        $disk = Storage::disk('local');
        $ruta = $this->resolverRutaArchivo($registroDocumento, $disk);

        abort_if($ruta === null, 404, 'El archivo no existe.');

        return response()->file($disk->path($ruta));
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
