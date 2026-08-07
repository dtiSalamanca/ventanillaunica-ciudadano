<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Tramite extends Model
{
    protected $table = 'cat_tramites';

    protected $primaryKey = 'id_tramite';

    protected $fillable = [
        'nombre_tramite',
        'descripcion_tramite',
        'estatus_tramite',
        'fk_dependencia',
        'precio_tramite',
        'cuenta_predial',
    ];

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'fk_dependencia', 'id_dependencia');
    }

    public function requisitos(): BelongsToMany
    {
        return $this->belongsToMany(Requisito::class, 'tbl_requisitos_tramites', 'fk_tramite', 'fk_requisito', 'id_tramite', 'id_requisito');
    }

    /**
     * Requisitos visibles del trámite resolviendo la tabla pivote
     * tbl_requisitos_tramites. Cada fila puede apuntar a:
     *  - fk_predio    → cat_documentos_predios (documento de predio)
     *  - fk_requisito → cat_documentos_personales (documento personal) o
     *                   cat_requisitos (requisito tradicional)
     *
     * @return Collection<int, object>
     */
    public function requisitosVisibles(): Collection
    {
        $pivots = RequisitoTramite::where('fk_tramite', $this->id_tramite)->get();

        return static::resolverRequisitosPivots($pivots);
    }

    /**
     * Construye un mapa id_tramite => requisitos visibles para varios trámites
     * en pocas consultas (evita N+1 en la vista de índice).
     *
     * @param  Collection<int, Tramite>  $tramites
     * @return array<int, Collection<int, object>>
     */
    public static function mapaRequisitosVisibles(Collection $tramites): array
    {
        $pivotsPorTramite = RequisitoTramite::whereIn('fk_tramite', $tramites->pluck('id_tramite'))
            ->get()
            ->groupBy('fk_tramite');

        $mapa = [];

        foreach ($tramites as $tramite) {
            $mapa[$tramite->id_tramite] = static::resolverRequisitosPivots(
                $pivotsPorTramite->get($tramite->id_tramite) ?? collect(),
            );
        }

        return $mapa;
    }

    /**
     * Convierte las filas pivote de un trámite en objetos de requisito visibles
     * (documento personal, documento de predio o requisito tradicional).
     *
     * @param  Collection<int, RequisitoTramite>  $pivots
     * @return Collection<int, object>
     */
    protected static function resolverRequisitosPivots(Collection $pivots): Collection
    {
        if ($pivots->isEmpty()) {
            return collect();
        }

        $idsPersonales = $pivots->pluck('fk_requisito')->filter()->unique()->values();
        $idsPredios = $pivots->pluck('fk_predio')->filter()->unique()->values();

        $documentosPersonales = catDocumentoPersonal::whereIn('id_documento', $idsPersonales)
            ->where('estatus_documento', 1)
            ->get()
            ->keyBy('id_documento');

        $documentosPredios = catDocumentoPredio::whereIn('id_documento_predio', $idsPredios)
            ->where('estatus_documento', 1)
            ->get()
            ->keyBy('id_documento_predio');

        $requisitos = Requisito::whereIn('id_requisito', $idsPersonales)
            ->where('estatus_requisito', 1)
            ->get()
            ->keyBy('id_requisito');

        $items = collect();

        foreach ($pivots as $pivot) {
            if ($pivot->fk_predio !== null) {
                $documento = $documentosPredios->get((int) $pivot->fk_predio);

                if ($documento === null) {
                    continue;
                }

                $items->push((object) [
                    'id_requisito' => 'predio_'.$documento->id_documento_predio,
                    'nombre_requisito' => $documento->nombre_documento,
                    'tipo' => 'predio',
                ]);
            } elseif ($pivot->fk_requisito !== null) {
                $documento = $documentosPersonales->get((int) $pivot->fk_requisito);

                if ($documento !== null) {
                    $items->push((object) [
                        'id_requisito' => 'personal_'.$documento->id_documento,
                        'nombre_requisito' => $documento->nombre_documento,
                        'tipo' => 'personal',
                    ]);

                    continue;
                }

                $requisito = $requisitos->get((int) $pivot->fk_requisito);

                if ($requisito !== null) {
                    $items->push((object) [
                        'id_requisito' => $requisito->id_requisito,
                        'nombre_requisito' => $requisito->nombre_requisito,
                        'tipo' => 'requisito',
                    ]);
                }
            }
        }

        return $items->sortBy('nombre_requisito')->values();
    }

    public function tramitesRequeridos(): BelongsToMany
    {
        return $this->belongsToMany(
            Tramite::class,
            'tbl_tramites_prerequisitos',
            'fk_tramite',
            'fk_tramite_requerido',
            'id_tramite',
            'id_tramite',
        );
    }
}
