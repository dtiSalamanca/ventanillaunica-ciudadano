<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'tbl_solicitudes';

    protected $primaryKey = 'id_solicitud';

    protected $fillable = [
        'fk_usuario',
        'fk_tramite',
        'fecha_solicitud',
        'fecha_resolucion',
        'observacion_solicitud',
        'validez_solicitud',
        'estatus_solicitud',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'validez_solicitud' => 'date',
        'estatus_solicitud' => 'integer',
    ];
}
