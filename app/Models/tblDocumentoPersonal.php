<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class tblDocumentoPersonal extends Model
{
    protected $table = 'cat_documentos_personales';

    protected $primaryKey = 'id_documento';

    protected $fillable = [
        'nombre_documento',
        'vigencia_meses',
        'estatus_documento',
    ];

    public function documentosPersonales(): HasMany
    {
        return $this->hasMany(catDocumentoPersonal::class, 'fk_documento_personal', 'id_documento');
    }
}
