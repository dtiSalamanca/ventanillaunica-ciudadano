<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PerfilesController;
use App\Http\Controllers\PrediosController;
use App\Http\Controllers\TramitesController;
use App\Http\Controllers\UsuariosController;
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
    Route::get('/perfiles/documentos/estatus', 'estatusDocumentos')->name('estatusDocumentos');
    Route::post('/perfiles/documentos/subir/{catalogoDocumento}', 'subirDocumento')->name('subirDocumento');
    Route::get('/perfiles/documentos/descargar/{registroDocumento}', 'descargarDocumento')->name('descargarDocumento');
});

Route::middleware('auth')->controller(PrediosController::class)->group(function () {
    Route::get('/perfiles/predios/estatus', 'estatusPredios')->name('estatusPredios');
    Route::post('/perfiles/predios/agregar', 'agregarPredio')->name('agregarPredio');
    Route::post('/perfiles/predios/actualizar/{predio}', 'actualizarPredio')->name('actualizarPredio');
    Route::post('/perfiles/predios/eliminar/{predio}', 'eliminarPredio')->name('eliminarPredio');
    Route::post('/perfiles/predios/subir/{predio}/{catalogoDocumento}', 'subirDocumentoPredio')->name('subirDocumentoPredio');
    Route::get('/perfiles/predios/descargar/{registroDocumento}', 'descargarDocumentoPredio')->name('descargarDocumentoPredio');
});

Route::middleware('auth')->controller(TramitesController::class)->group(function () {
    Route::get('/tramites', 'indexTramites')->name('indexTramites');
    Route::get('/tramites/mis-tramites', 'misTramites')->name('misTramites');
    Route::get('/tramites/iniciar/{tramite}', 'iniciarTramite')->name('iniciarTramite');
    Route::post('/tramites/enviar-solicitud', 'enviarSolicitud')->name('enviarSolicitud');
});

Route::middleware('auth')->controller(UsuariosController::class)->group(function () {
    Route::get('/perfiles/cambiar-contrasena', 'cambiarContrasena')->name('cambiarContrasena');
    Route::post('/perfiles/cambiar-contrasena', 'actualizarContrasena')->name('actualizarContrasena');
});
