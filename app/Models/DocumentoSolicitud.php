<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoSolicitud extends Model
{
    protected $table = 'tbl_documentos_solicitud';

    protected $primaryKey = 'id_documento';

    protected $fillable = [
        'fk_solicitud',
        'documento_solicitud',
    ];
}
