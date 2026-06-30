<?php

use App\Http\Controllers\HomeController;
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
