<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class catDocumentoPersonal extends Model
{
    protected $table = 'tbl_documentos_personales';

    protected $primaryKey = 'id_documento';

    protected $fillable = [
        'fk_usuario',
        'fk_documento_personal',
        'fecha_registro',
        'estatus_documento',
        'ruta_archivo',
    ];

    const ESTATUS_RECHAZADO = 0;

    const ESTATUS_EN_REVISION = 1;

    const ESTATUS_APROBADO = 2;

    public function directorioArchivo(): string
    {
        return "documentos_personales/{$this->fk_usuario}/{$this->fk_documento_personal}";
    }

    protected $casts = [
        'fecha_registro' => 'date',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_usuario', 'id');
    }

    public function catalogoDocumento(): BelongsTo
    {
        return $this->belongsTo(tblDocumentoPersonal::class, 'fk_documento_personal', 'id_documento');
    }
}
