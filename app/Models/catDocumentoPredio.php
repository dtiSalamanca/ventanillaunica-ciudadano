<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class catDocumentoPredio extends Model
{
    protected $table = 'cat_documentos_predios';

    protected $primaryKey = 'id_documento_predio';

    protected $fillable = [
        'nombre_documento',
        'vigencia_meses',
        'estatus_documento',
    ];

    public function documentosPredios(): HasMany
    {
        return $this->hasMany(DocumentoPredio::class, 'fk_cat_documento_predio', 'id_documento_predio');
    }
}
