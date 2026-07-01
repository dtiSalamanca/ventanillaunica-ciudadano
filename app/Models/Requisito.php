<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Requisito extends Model
{
    protected $table = 'cat_requisitos';

    protected $primaryKey = 'id_requisito';

    protected $fillable = [
        'nombre_requisito',
        'estatus_requisito',
    ];

    public function tramites(): BelongsToMany
    {
        return $this->belongsToMany(Tramite::class, 'tbl_requisitos_tramites', 'fk_requisito', 'fk_tramite', 'id_requisito', 'id_tramite');
    }
}
