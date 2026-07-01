<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tramite extends Model
{
    protected $table = 'cat_tramites';

    protected $primaryKey = 'id_tramite';

    protected $fillable = [
        'nombre_tramite',
        'estatus_tramite',
        'fk_dependencia',
    ];

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'fk_dependencia', 'id_dependencia');
    }

    public function requisitos(): BelongsToMany
    {
        return $this->belongsToMany(Requisito::class, 'tbl_requisitos_tramites', 'fk_tramite', 'fk_requisito', 'id_tramite', 'id_requisito');
    }
}
