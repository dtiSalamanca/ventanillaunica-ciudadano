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
        $request->file('archivo')->store($directorio, 'local');

        if ($registroExistente) {
            $registroExistente->update([
                'fecha_registro' => now(),
                'estatus_documento' => catDocumentoPersonal::ESTATUS_EN_REVISION,
            ]);
        } else {
            catDocumentoPersonal::create([
                'fk_usuario' => auth()->id(),
                'fk_documento_personal' => $catalogoDocumento->id_documento,
                'fecha_registro' => now(),
                'estatus_documento' => catDocumentoPersonal::ESTATUS_EN_REVISION,
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
            ]);
        }

        return redirect()->route('indexPerfiles')
            ->with('success', "El documento «{$catalogoDocumento->nombre_documento}» se envió para revisión correctamente.");
    }

    public function descargarDocumento(catDocumentoPersonal $registroDocumento): BinaryFileResponse
    {
        abort_if($registroDocumento->fk_usuario !== auth()->id(), 403);

        $archivos = Storage::disk('local')->files($registroDocumento->directorioArchivo());

        abort_if(empty($archivos), 404, 'El archivo no existe.');

        return response()->file(
            Storage::disk('local')->path($archivos[0])
        );
    }
}
