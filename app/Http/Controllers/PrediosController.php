<?php

namespace App\Http\Controllers;

use App\Models\catDocumentoPredio;
use App\Models\DocumentoPredio;
use App\Models\Predio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrediosController extends Controller
{
    public function agregarPredio(Request $request): RedirectResponse
    {
        $request->validate([
            'clave_predio' => ['required', 'string', 'max:255'],
        ], [
            'clave_predio.required' => 'Debes capturar la clave catastral del predio.',
            'clave_predio.max' => 'La clave catastral no puede superar los 255 caracteres.',
        ]);

        Predio::create([
            'clave_predio' => $request->input('clave_predio'),
            'estatus_predio' => Predio::ESTATUS_EN_REVISION,
            'fk_user' => auth()->id(),
        ]);

        return redirect()->route('indexPerfiles')
            ->with('success', 'El predio se agregó correctamente y quedó en revisión.');
    }

    public function eliminarPredio(Predio $predio): RedirectResponse
    {
        abort_if($predio->fk_user !== auth()->id(), 403);
        abort_if($predio->estatus_predio === Predio::ESTATUS_APROBADO, 403, 'No puedes eliminar un predio aprobado.');

        Storage::disk('local')->deleteDirectory("documentos_predios/{$predio->fk_user}/{$predio->id_predio}");
        $predio->delete();

        return redirect()->route('indexPerfiles')
            ->with('success', 'El predio se eliminó correctamente.');
    }

    public function subirDocumentoPredio(Request $request, Predio $predio, catDocumentoPredio $catalogoDocumento): RedirectResponse|JsonResponse
    {
        abort_if($predio->fk_user !== auth()->id(), 403);

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.file' => 'El archivo no es válido.',
            'archivo.mimes' => 'Solo se permiten archivos PDF.',
            'archivo.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        $directorio = "documentos_predios/{$predio->fk_user}/{$predio->id_predio}/{$catalogoDocumento->id_documento_predio}";

        $registroExistente = DocumentoPredio::where('fk_predio', $predio->id_predio)
            ->where('fk_cat_documento_predio', $catalogoDocumento->id_documento_predio)
            ->first();

        if ($registroExistente) {
            Storage::disk('local')->deleteDirectory($directorio);
        }

        $rutaArchivo = $request->file('archivo')->store($directorio, 'local');

        if ($registroExistente) {
            $registroExistente->update([
                'estatus_documento' => DocumentoPredio::ESTATUS_EN_REVISION,
                'ruta_documento' => $rutaArchivo,
            ]);
            $registro = $registroExistente;
        } else {
            $registro = DocumentoPredio::create([
                'fk_predio' => $predio->id_predio,
                'fk_cat_documento_predio' => $catalogoDocumento->id_documento_predio,
                'estatus_documento' => DocumentoPredio::ESTATUS_EN_REVISION,
                'ruta_documento' => $rutaArchivo,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id_registro' => $registro->id_documento_predio,
                'fk_predio' => $predio->id_predio,
                'fk_cat_documento_predio' => $catalogoDocumento->id_documento_predio,
                'fecha_registro' => $registro->created_at->format('d/m/Y'),
                'estatus' => $registro->estatus_documento,
                'url_descargar' => route('descargarDocumentoPredio', $registro->id_documento_predio),
                'url_subir' => route('subirDocumentoPredio', [$predio->id_predio, $catalogoDocumento->id_documento_predio]),
            ]);
        }

        return redirect()->route('indexPerfiles')
            ->with('success', "El documento «{$catalogoDocumento->nombre_documento}» se envió para revisión correctamente.");
    }

    public function descargarDocumentoPredio(DocumentoPredio $registroDocumento): BinaryFileResponse
    {
        abort_if($registroDocumento->predio->fk_user !== auth()->id(), 403);

        $disk = Storage::disk('local');

        abort_if(! $disk->exists($registroDocumento->ruta_documento), 404, 'El archivo no existe.');

        return response()->file($disk->path($registroDocumento->ruta_documento));
    }

    public function estatusPredios(): JsonResponse
    {
        $predios = Predio::where('fk_user', auth()->id())
            ->with('documentos')
            ->get()
            ->map(fn (Predio $predio) => [
                'id_predio' => $predio->id_predio,
                'estatus' => $predio->estatus_predio,
                'documentos' => $predio->documentos->map(fn (DocumentoPredio $documento) => [
                    'fk_predio' => $documento->fk_predio,
                    'fk_cat_documento_predio' => $documento->fk_cat_documento_predio,
                    'estatus' => $documento->estatus_documento,
                    'fecha_registro' => $documento->created_at->format('d/m/Y'),
                    'url_descargar' => route('descargarDocumentoPredio', $documento->id_documento_predio),
                    'url_subir' => route('subirDocumentoPredio', [$documento->fk_predio, $documento->fk_cat_documento_predio]),
                ]),
            ]);

        return response()->json(['predios' => $predios]);
    }
}
