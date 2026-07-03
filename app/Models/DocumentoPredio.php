<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoPredio extends Model
{
    public const ESTATUS_RECHAZADO = 0;

    public const ESTATUS_EN_REVISION = 1;

    public const ESTATUS_APROBADO = 2;

    protected $table = 'tbl_documentos_predios';

    protected $primaryKey = 'id_documento_predio';

    protected $fillable = [
        'fk_cat_documento_predio',
        'ruta_documento',
        'fk_predio',
        'estatus_documento',
    ];

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class, 'fk_predio', 'id_predio');
    }

    public function catalogoDocumento(): BelongsTo
    {
        return $this->belongsTo(catDocumentoPredio::class, 'fk_cat_documento_predio', 'id_documento_predio');
    }
}
