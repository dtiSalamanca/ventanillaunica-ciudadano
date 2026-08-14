<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;

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
        'fk_usuario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_usuario', 'id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoPredio::class, 'fk_predio', 'id_predio');
    }

    /**
     * Consulta los adeudos de la cuenta predial contra el sistema de recibo
     * predial de Salamanca.
     *
     * Devuelve un arreglo con la forma:
     * - ['estado' => 'al_corriente', 'mensaje' => string]
     * - ['estado' => 'adeudos', 'mensaje' => string, 'adeudos' => int]
     * - ['estado' => 'no_encontrado', 'mensaje' => string] (no se pudo verificar)
     *
     * @return array{estado: string, mensaje: string, adeudos?: int}
     */
    public function consultarAdeudo(): array
    {
        $baseUrl = rtrim((string) config('services.recibo_predial.url'), '/');
        $url = $baseUrl.'/api/consulta/predio/adeudo/'.urlencode($this->clave_predio);

        try {
            $respuesta = Http::timeout(10)->connectTimeout(5)->get($url);

            if (! $respuesta->ok()) {
                return $this->respuestaSinRegistros();
            }

            $datos = $respuesta->json();

            if (! is_array($datos) || ! array_key_exists('tiene', $datos)) {
                return $this->respuestaSinRegistros();
            }

            $tiene = filter_var($datos['tiene'], FILTER_VALIDATE_BOOLEAN);

            if ($tiene) {
                return [
                    'estado' => 'adeudos',
                    'mensaje' => (string) ($datos['mensaje'] ?? 'La cuenta predial tiene adeudos pendientes.'),
                    'adeudos' => (int) ($datos['adeudos'] ?? 0),
                ];
            }

            return [
                'estado' => 'al_corriente',
                'mensaje' => (string) ($datos['mensaje'] ?? 'La cuenta está al corriente, no tiene adeudos.'),
            ];
        } catch (\Throwable $e) {
            return $this->respuestaSinRegistros();
        }
    }

    /**
     * @return array{estado: string, mensaje: string}
     */
    private function respuestaSinRegistros(): array
    {
        return [
            'estado' => 'no_encontrado',
            'mensaje' => 'No se encontraron registros de esa cuenta predial.',
        ];
    }
}
