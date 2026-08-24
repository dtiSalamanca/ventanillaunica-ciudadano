<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
        'fecha_aprobacion',
    ];

    protected $casts = [
        'fecha_aprobacion' => 'date',
    ];

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class, 'fk_predio', 'id_predio');
    }

    public function catalogoDocumento(): BelongsTo
    {
        return $this->belongsTo(catDocumentoPredio::class, 'fk_cat_documento_predio', 'id_documento_predio');
    }

    /**
     * Fecha de vencimiento de la vigencia del documento. La vigencia corre a
     * partir de la fecha de aprobación (respaldo: fecha de creación) más la
     * vigencia en meses del catálogo. Devuelve null si el documento no expira.
     */
    public function fechaVencimiento(): ?Carbon
    {
        $meses = (int) ($this->catalogoDocumento?->vigencia_meses ?? 0);

        if ($meses <= 0) {
            return null;
        }

        $base = $this->fecha_aprobacion ?? $this->created_at;

        if ($base === null) {
            return null;
        }

        return $base->copy()->addMonths($meses);
    }

    /**
     * Indica si la vigencia del documento ya venció. El documento sigue siendo
     * válido el día del vencimiento y queda expirado a partir del día siguiente.
     */
    public function estaExpirado(): bool
    {
        $vencimiento = $this->fechaVencimiento();

        return $vencimiento !== null && now()->startOfDay()->greaterThan($vencimiento->copy()->startOfDay());
    }

    /**
     * Días naturales restantes antes de que venza la vigencia (negativo si ya
     * venció). Devuelve null si el documento no expira.
     */
    public function diasParaVencer(): ?int
    {
        $vencimiento = $this->fechaVencimiento();

        if ($vencimiento === null) {
            return null;
        }

        return now()->startOfDay()->diffInDays($vencimiento->copy()->startOfDay(), false);
    }

    /**
     * Indica si el documento está por vencer (dentro de los días indicados) o
     * ya venció.
     */
    public function estaPorVencer(int $dias = 3): bool
    {
        $diasRestantes = $this->diasParaVencer();

        return $diasRestantes !== null && $diasRestantes <= $dias;
    }
}
