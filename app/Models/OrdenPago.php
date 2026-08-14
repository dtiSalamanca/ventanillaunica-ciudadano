<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenPago extends Model
{
    protected $table = 'ordenes_pagos';

    protected $primaryKey = 'id_orden_pago';

    protected $fillable = [
        'nombre_tramite',
        'precio_tramite',
        'numero_cri',
        'orden_estatus',
        'folio_pago',
        'fk_tramite',
    ];

    protected $casts = [
        'precio_tramite' => 'decimal:2',
        'numero_cri' => 'integer',
        'orden_estatus' => 'integer',
    ];

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class, 'fk_tramite', 'id_tramite');
    }
}
