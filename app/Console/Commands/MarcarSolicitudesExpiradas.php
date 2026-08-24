<?php

namespace App\Console\Commands;

use App\Models\Solicitud;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:marcar-solicitudes-expiradas')]
#[Description('Marca como Completado (5) las solicitudes con folio y como Expirado (6) las que rebasaron su vigencia.')]
class MarcarSolicitudesExpiradas extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1) Normalizar Completado (5): las solicitudes que el administrador marcó
        //    como 4 (legado) o cuya orden de pago ya tiene folio (ya se pagó) pasan
        //    a estatus 5. Se omiten las que ya están en 5 o 6.
        $marcadasCompletadas = Solicitud::query()
            ->where(function ($query) {
                $query->where('estatus_solicitud', 4)
                    ->orWhereHas('ordenPago', fn ($orden) => $orden
                        ->whereNotNull('folio_pago')
                        ->where('folio_pago', '!=', ''));
            })
            ->whereNotIn('estatus_solicitud', [5, 6])
            ->update([
                'estatus_solicitud' => 5,
                'updated_at' => now(),
            ]);

        // 2) Marcar como Expirado (6) las solicitudes completadas cuya vigencia
        //    (validez_solicitud o fecha_resolucion + vigencia_dias) ya venció.
        $expiradas = 0;
        Solicitud::with(['tramite', 'ordenPago'])
            ->where('estatus_solicitud', 5)
            ->chunkById(100, function ($solicitudes) use (&$expiradas) {
                foreach ($solicitudes as $solicitud) {
                    if ($solicitud->estaExpirada()) {
                        $solicitud->estatus_solicitud = 6;
                        $solicitud->save();
                        $expiradas++;
                    }
                }
            });

        $this->info("Solicitudes marcadas como Completado (5): {$marcadasCompletadas}");
        $this->info("Solicitudes marcadas como Expirado (6): {$expiradas}");

        return self::SUCCESS;
    }
}
