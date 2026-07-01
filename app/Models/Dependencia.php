<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dependencia extends Model
{
    protected $table = 'cat_dependencias';

    protected $primaryKey = 'id_dependencia';

    protected $fillable = [
        'nombre_dependencia',
        'estatus_dependencia',
    ];

    protected function casts(): array
    {
        return [
            'estatus_dependencia' => 'boolean',
        ];
    }

    public function tramites(): HasMany
    {
        return $this->hasMany(Tramite::class, 'fk_dependencia', 'id_dependencia');
    }
}
