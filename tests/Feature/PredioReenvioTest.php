<?php

namespace Tests\Feature;

use App\Models\Predio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PredioReenvioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // tbl_predios se gestiona en el proyecto administrador (fuera de este
        // repositorio), así que se crea explícitamente para el entorno de pruebas.
        Schema::create('tbl_predios', function ($table) {
            $table->bigIncrements('id_predio');
            $table->string('clave_predio');
            $table->integer('estatus_predio')->default(-1);
            $table->tinyInteger('consultado')->default(0);
            $table->text('motivo_rechazo')->nullable();
            $table->unsignedBigInteger('fk_usuario');
            $table->timestamps();
        });
    }

    public function test_corregir_predio_rechazado_lo_reenvia_a_revision_para_consultar_de_nuevo(): void
    {
        $usuario = User::factory()->create();

        $predio = Predio::create([
            'clave_predio' => '1234567890',
            'estatus_predio' => Predio::ESTATUS_RECHAZADO,
            'consultado' => Predio::CONSULTADO_NO_EXISTE,
            'motivo_rechazo' => 'El predio registrado no existe en el sistema de predial.',
            'fk_usuario' => $usuario->id,
        ]);

        $response = $this->actingAs($usuario)
            ->post(route('actualizarPredio', $predio), [
                'clave_predio_'.$predio->id_predio => '9876543210',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tbl_predios', [
            'id_predio' => $predio->id_predio,
            'clave_predio' => '9876543210',
            'estatus_predio' => Predio::ESTATUS_EN_REVISION,
            'consultado' => Predio::CONSULTADO_SIN_CONSULTAR,
            'motivo_rechazo' => null,
        ]);
    }

    public function test_otro_usuario_no_puede_corregir_un_predio_ajeno(): void
    {
        $dueno = User::factory()->create();
        $otro = User::factory()->create();

        $predio = Predio::create([
            'clave_predio' => '1234567890',
            'estatus_predio' => Predio::ESTATUS_RECHAZADO,
            'consultado' => Predio::CONSULTADO_NO_EXISTE,
            'motivo_rechazo' => 'El predio registrado no existe en el sistema de predial.',
            'fk_usuario' => $dueno->id,
        ]);

        $response = $this->actingAs($otro)
            ->post(route('actualizarPredio', $predio), [
                'clave_predio_'.$predio->id_predio => '9876543210',
            ]);

        $response->assertForbidden();
    }
}
