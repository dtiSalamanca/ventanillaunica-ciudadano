<?php

use App\Http\Controllers\Api\DocumentoPersonalController;
use App\Http\Controllers\Api\DocumentoPredioController;
use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->group(function () {
    Route::get('/documentos-personales/{registroDocumento}/archivo', [DocumentoPersonalController::class, 'mostrarArchivo'])
        ->name('api.documentos-personales.archivo');

    Route::get('/documentos-predios/{registroDocumento}/archivo', [DocumentoPredioController::class, 'mostrarArchivo'])
        ->name('api.documentos-predios.archivo');
});
