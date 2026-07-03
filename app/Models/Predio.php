<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Predio extends Model
{
    public const ESTATUS_RECHAZADO = 0;

    public const ESTATUS_EN_REVISION = 1;

    public const ESTATUS_APROBADO = 2;

    protected $table = 'tbl_predios';

    protected $primaryKey = 'id_predio';

    protected $fillable = [
        'clave_predio',
        'estatus_predio',
        'fk_user',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_user', 'id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoPredio::class, 'fk_predio', 'id_predio');
    }
}
