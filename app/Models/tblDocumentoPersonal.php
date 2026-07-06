<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class tblDocumentoPersonal extends Model
{
    public const ESTATUS_RECHAZADO = 0;

    public const ESTATUS_EN_REVISION = 1;

    public const ESTATUS_APROBADO = 2;

    protected $table = 'tbl_documentos_personales';

    protected $primaryKey = 'id_documento';

    protected $fillable = [
        'fk_usuario',
        'fk_documento_personal',
        'fecha_registro',
        'estatus_documento',
        'ruta_archivo',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_usuario', 'id');
    }

    public function catalogoDocumento(): BelongsTo
    {
        return $this->belongsTo(catDocumentoPersonal::class, 'fk_documento_personal', 'id_documento');
    }
}
