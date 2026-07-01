<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitoTramite extends Model
{
    protected $table = 'tbl_requisitos_tramites';

    protected $primaryKey = 'id_requisito';

    protected $fillable = [
        'fk_requisito',
        'fk_tramite',
    ];

    public function requisito(): BelongsTo
    {
        return $this->belongsTo(Requisito::class, 'fk_requisito', 'id_requisito');
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class, 'fk_tramite', 'id_tramite');
    }
}
