<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
