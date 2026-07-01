<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoTramite extends Model
{
    protected $table = 'tbl_documentos_tramites';

    protected $primaryKey = 'id_documento';

    protected $fillable = [
        'fk_requisito',
        'fk_documento_personal',
        'fk_documento_solicitud',
        'fk_solicitud',
    ];
}
