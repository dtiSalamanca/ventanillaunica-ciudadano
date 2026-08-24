<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Marca automáticamente como Expirado (6) las solicitudes completadas (5)
// cuya vigencia haya vencido, y normaliza a 5 las que ya tienen folio.
Schedule::command('app:marcar-solicitudes-expiradas')
    ->daily()
    ->withoutOverlapping();
