<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TurnadoSolicitud extends Model
{
    protected $table = 'tbl_turnados_solicitudes';

    protected $primaryKey = 'id_turnado';

    protected $fillable = [
        'fk_usuario_ad',
        'fk_solicitud',
        'estatus_turnado',
    ];

    protected function casts(): array
    {
        return [
            'estatus_turnado' => 'boolean',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'fk_solicitud', 'id_solicitud');
    }

    public function resolucion(): HasOne
    {
        return $this->hasOne(ResolucionSolicitud::class, 'fk_turnado', 'id_turnado');
    }
}
