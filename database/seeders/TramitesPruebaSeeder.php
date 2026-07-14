<?php

namespace Database\Seeders;

use App\Models\Dependencia;
use App\Models\Requisito;
use App\Models\Tramite;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Puebla el catálogo de trámites, dependencias y requisitos con datos
 * realistas del municipio de Salamanca, Guanajuato. Reemplaza cualquier
 * registro previo de las tablas del dominio para mantener la vista limpia.
 */
class TramitesPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->limpiarTablas();

        $requisitos = $this->sembrarRequisitos();
        $dependencias = $this->sembrarDependencias();
        $this->sembrarTramites($dependencias, $requisitos);
    }

    /**
     * Elimina los registros existentes del dominio en orden de dependencias
     * para respetar las llaves foráneas.
     */
    private function limpiarTablas(): void
    {
        DB::table('tbl_requisitos_tramites')->delete();
        DB::table('cat_tramites')->delete();
        DB::table('cat_requisitos')->delete();
        DB::table('cat_dependencias')->delete();
    }

    /**
     * @return Collection<string, Requisito> Nombre del requisito => modelo.
     */
    private function sembrarRequisitos(): Collection
    {
        $nombres = [
            'Identificación oficial vigente (INE/IFE)',
            'Clave Única de Registro de Población (CURP)',
            'Comprobante de domicilio (no mayor a 3 meses)',
            'Escrituras del predio debidamente inscritas',
            'Plano arquitectónico autorizado',
            'Recibo de pago del impuesto predial del año en curso',
            'Solicitud por escrito dirigida al titular del área',
            'Fotografías del inmueble (frente y lateral)',
            'Acta de nacimiento',
            'Comprobante de pago de derechos',
            'Registro Federal de Contribuyentes (RFC)',
            'Poder legal o carta poder (en caso de representación)',
        ];

        return collect($nombres)->mapWithKeys(function (string $nombre): array {
            $requisito = Requisito::create([
                'nombre_requisito' => $nombre,
                'estatus_requisito' => 1,
            ]);

            return [$nombre => $requisito];
        });
    }

    /**
     * @return Collection<string, Dependencia> Nombre de la dependencia => modelo.
     */
    private function sembrarDependencias(): Collection
    {
        $nombres = [
            'Desarrollo Urbano y Ordenamiento Territorial',
            'Tesorería Municipal',
            'Registro Civil',
            'Catastro Municipal',
            'Obras Públicas',
            'Servicios Públicos Municipales',
        ];

        return collect($nombres)->mapWithKeys(function (string $nombre): array {
            $dependencia = Dependencia::create([
                'nombre_dependencia' => $nombre,
                'estatus_dependencia' => true,
            ]);

            return [$nombre => $dependencia];
        });
    }

    /**
     * @param  Collection<string, Dependencia>  $dependencias
     * @param  Collection<string, Requisito>  $requisitos
     */
    private function sembrarTramites(Collection $dependencias, Collection $requisitos): void
    {
        // 'Nombre del trámite' => ['dependencia' => ..., 'cuenta_predial' => ..., 'requisitos' => [...]]
        $tramites = [
            'Licencia de Construcción' => [
                'dependencia' => 'Desarrollo Urbano y Ordenamiento Territorial',
                'cuenta_predial' => 1,
                'precio' => 1850.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Comprobante de domicilio (no mayor a 3 meses)',
                    'Escrituras del predio debidamente inscritas',
                    'Plano arquitectónico autorizado',
                    'Solicitud por escrito dirigida al titular del área',
                    'Fotografías del inmueble (frente y lateral)',
                ],
            ],
            'Licencia de Uso de Suelo' => [
                'dependencia' => 'Desarrollo Urbano y Ordenamiento Territorial',
                'cuenta_predial' => 1,
                'precio' => 1200.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Comprobante de domicilio (no mayor a 3 meses)',
                    'Escrituras del predio debidamente inscritas',
                    'Solicitud por escrito dirigida al titular del área',
                ],
            ],
            'Manifestación de Construcción' => [
                'dependencia' => 'Desarrollo Urbano y Ordenamiento Territorial',
                'cuenta_predial' => 1,
                'precio' => 950.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Escrituras del predio debidamente inscritas',
                    'Plano arquitectónico autorizado',
                    'Solicitud por escrito dirigida al titular del área',
                ],
            ],
            'Pago de Impuesto Predial' => [
                'dependencia' => 'Tesorería Municipal',
                'cuenta_predial' => 1,
                'precio' => 0.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Recibo de pago del impuesto predial del año en curso',
                    'Solicitud por escrito dirigida al titular del área',
                ],
            ],
            'Constancia de No Adeudo de Predial' => [
                'dependencia' => 'Tesorería Municipal',
                'cuenta_predial' => 1,
                'precio' => 150.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Solicitud por escrito dirigida al titular del área',
                ],
            ],
            'Acta de Nacimiento (copia certificada)' => [
                'dependencia' => 'Registro Civil',
                'cuenta_predial' => 0,
                'precio' => 120.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Comprobante de pago de derechos',
                ],
            ],
            'Acta de Matrimonio' => [
                'dependencia' => 'Registro Civil',
                'cuenta_predial' => 0,
                'precio' => 250.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Acta de nacimiento',
                    'Comprobante de pago de derechos',
                ],
            ],
            'Avalúo Catastral' => [
                'dependencia' => 'Catastro Municipal',
                'cuenta_predial' => 1,
                'precio' => 700.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Escrituras del predio debidamente inscritas',
                    'Comprobante de domicilio (no mayor a 3 meses)',
                    'Comprobante de pago de derechos',
                ],
            ],
            'Constancia Catastral' => [
                'dependencia' => 'Catastro Municipal',
                'cuenta_predial' => 1,
                'precio' => 350.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Escrituras del predio debidamente inscritas',
                ],
            ],
            'Permiso de Obra Menor' => [
                'dependencia' => 'Obras Públicas',
                'cuenta_predial' => 1,
                'precio' => 650.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Clave Única de Registro de Población (CURP)',
                    'Comprobante de domicilio (no mayor a 3 meses)',
                    'Escrituras del predio debidamente inscritas',
                    'Fotografías del inmueble (frente y lateral)',
                    'Solicitud por escrito dirigida al titular del área',
                ],
            ],
            'Solicitud de Baja de Servicios (agua/alumbrado)' => [
                'dependencia' => 'Servicios Públicos Municipales',
                'cuenta_predial' => 0,
                'precio' => 80.00,
                'requisitos' => [
                    'Identificación oficial vigente (INE/IFE)',
                    'Comprobante de domicilio (no mayor a 3 meses)',
                    'Solicitud por escrito dirigida al titular del área',
                ],
            ],
        ];

        foreach ($tramites as $nombreTramite => $config) {
            $tramite = Tramite::create([
                'nombre_tramite' => $nombreTramite,
                'estatus_tramite' => 1,
                'fk_dependencia' => $dependencias[$config['dependencia']]->id_dependencia,
                'cuenta_predial' => $config['cuenta_predial'],
                'precio_tramite' => $config['precio'],
            ]);

            foreach ($config['requisitos'] as $nombreRequisito) {
                DB::table('tbl_requisitos_tramites')->insert([
                    'fk_tramite' => $tramite->id_tramite,
                    'fk_requisito' => $requisitos[$nombreRequisito]->id_requisito,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
