<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResolucionSolicitud extends Model
{
    protected $table = 'tbl_resoluciones_solicitudes';

    protected $primaryKey = 'id_resolucion';

    protected $fillable = [
        'fk_turnado',
        'resolucion_solicitud',
        'documento_resolucion',
    ];
}
