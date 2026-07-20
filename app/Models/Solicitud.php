<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitud extends Model
{
    protected $table = 'tbl_solicitudes';

    protected $primaryKey = 'id_solicitud';

    protected $fillable = [
        'fk_usuario',
        'fk_tramite',
        'fk_predio',
        'fecha_solicitud',
        'fecha_resolucion',
        'observacion_solicitud',
        'validez_solicitud',
        'estatus_solicitud',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_resolucion' => 'datetime',
        'validez_solicitud' => 'date',
        'estatus_solicitud' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_usuario', 'id');
    }

    public function tramite(): BelongsTo
    {
        return $this->belongsTo(Tramite::class, 'fk_tramite', 'id_tramite');
    }

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class, 'fk_predio', 'id_predio');
    }

    public function documentosTramites(): HasMany
    {
        return $this->hasMany(DocumentoTramite::class, 'fk_solicitud', 'id_solicitud');
    }
}
