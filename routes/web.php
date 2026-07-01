<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PerfilesController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes(['verify' => true]);

Route::get('/email/resend', function () {
    return view('auth.resend-verification');
})->name('verification.resend.form');

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')->controller(PerfilesController::class)->group(function () {
    Route::get('/perfiles/mi-perfil', 'indexPerfiles')->name('indexPerfiles');
    Route::get('/perfiles/documentos/estatus', 'estatusDocumentos')->name('perfiles.documentos.estatus');
    Route::post('/perfiles/documentos/{catalogoDocumento}', 'subirDocumento')->name('perfiles.documentos.subir');
    Route::get('/perfiles/documentos/{registroDocumento}/descargar', 'descargarDocumento')->name('perfiles.documentos.descargar');
});
