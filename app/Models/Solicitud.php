<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Carbon;

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

    /**
     * Orden de pago asociada a ESTA solicitud (no a otra del mismo trámite).
     */
    public function ordenPago(): HasOne
    {
        return $this->hasOne(OrdenPago::class, 'fk_solicitud', 'id_solicitud');
    }

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class, 'fk_predio', 'id_predio');
    }

    public function documentosTramites(): HasMany
    {
        return $this->hasMany(DocumentoTramite::class, 'fk_solicitud', 'id_solicitud');
    }

    public function turnados(): HasMany
    {
        return $this->hasMany(TurnadoSolicitud::class, 'fk_solicitud', 'id_solicitud');
    }

    /**
     * Resolución más reciente asociada a la solicitud a través de su turnado.
     */
    public function resolucion(): HasOneThrough
    {
        return $this->hasOneThrough(
            ResolucionSolicitud::class,
            TurnadoSolicitud::class,
            'fk_solicitud',
            'fk_turnado',
            'id_solicitud',
            'id_turnado',
        );
    }

    /**
     * Estado efectivo de la solicitud replicando la lógica visual de "Mis trámites":
     *  - Con orden de pago con folio (ya pagada) → 5 (Completado).
     *  - Con orden de pago sin folio → 3 (Por pagar).
     *  - Sin orden de pago → estatus_solicitud (4 legado o 5 = Completado).
     *  - Si está completada y su vigencia ya venció → 6 (Expirado).
     */
    public function estadoMostrado(): int
    {
        $ordenPago = $this->ordenPago;

        if ($ordenPago !== null) {
            $estado = filled($ordenPago->folio_pago) ? 5 : 3;
        } else {
            $estado = $this->estatus_solicitud;
        }

        // El administrador puede marcar Completado como 4 (legado) o 5 (nuevo);
        // ambos equivalen a Completado (5).
        if (in_array($estado, [4, 5], true)) {
            $estado = 5;
        }

        // Si está completado y su vigencia venció → Expirado (6).
        if ($estado === 5 && $this->estaExpirada()) {
            return 6;
        }

        return $estado;
    }

    /**
     * Fecha de vencimiento de la vigencia del trámite completado. Usa
     * validez_solicitud si está definida; si no, la calcula desde
     * fecha_resolucion + vigencia_dias del catálogo del trámite.
     */
    public function fechaVencimiento(): ?Carbon
    {
        if ($this->validez_solicitud !== null) {
            return $this->validez_solicitud;
        }

        $vigencia = (int) ($this->tramite?->vigencia_dias ?? 0);

        if ($vigencia <= 0 || $this->fecha_resolucion === null) {
            return null;
        }

        return $this->fecha_resolucion->copy()->addDays($vigencia);
    }

    /**
     * Indica si la solicitud ya está completada y su vigencia venció.
     */
    public function estaExpirada(): bool
    {
        $vencimiento = $this->fechaVencimiento();

        return $vencimiento !== null && now()->greaterThan($vencimiento);
    }
}
