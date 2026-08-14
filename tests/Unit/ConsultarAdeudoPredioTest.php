<?php

namespace Tests\Unit;

use App\Models\Predio;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsultarAdeudoPredioTest extends TestCase
{
    public function test_devuelve_adeudos_cuando_la_cuenta_tiene_adeudos(): void
    {
        Http::fake([
            'https://recibopredial.salamanca.gob.mx/api/consulta/predio/adeudo/*' => Http::response([
                'mensaje' => 'Hay 3 adeudo(s) pendiente(s) para esta cuenta predial.',
                'adeudos' => 3,
                'tiene' => true,
            ]),
        ]);

        $predio = new Predio(['clave_predio' => '25G001331001']);

        $resultado = $predio->consultarAdeudo();

        $this->assertSame('adeudos', $resultado['estado']);
        $this->assertSame(3, $resultado['adeudos']);
        $this->assertStringContainsString('3 adeudo(s)', $resultado['mensaje']);
    }

    public function test_devuelve_al_corriente_cuando_la_cuenta_no_tiene_adeudos(): void
    {
        Http::fake([
            'https://recibopredial.salamanca.gob.mx/api/consulta/predio/adeudo/*' => Http::response([
                'mensaje' => 'La cuenta está al corriente, no tiene adeudos.',
                'tiene' => false,
            ]),
        ]);

        $predio = new Predio(['clave_predio' => '25A000142060']);

        $resultado = $predio->consultarAdeudo();

        $this->assertSame('al_corriente', $resultado['estado']);
    }

    public function test_devuelve_no_encontrado_cuando_el_servicio_responde_error(): void
    {
        Http::fake([
            'https://recibopredial.salamanca.gob.mx/api/consulta/predio/adeudo/*' => Http::response('Error interno', 500),
        ]);

        $predio = new Predio(['clave_predio' => '25A000142060']);

        $resultado = $predio->consultarAdeudo();

        $this->assertSame('no_encontrado', $resultado['estado']);
        $this->assertSame('No se encontraron registros de esa cuenta predial.', $resultado['mensaje']);
    }

    public function test_devuelve_no_encontrado_cuando_no_hay_conexion(): void
    {
        Http::fake([
            'https://recibopredial.salamanca.gob.mx/api/consulta/predio/adeudo/*' => fn () => throw new ConnectionException('Conexión rechazada'),
        ]);

        $predio = new Predio(['clave_predio' => '25A000142060']);

        $resultado = $predio->consultarAdeudo();

        $this->assertSame('no_encontrado', $resultado['estado']);
    }

    public function test_devuelve_no_encontrado_cuando_la_respuesta_no_es_valida(): void
    {
        Http::fake([
            'https://recibopredial.salamanca.gob.mx/api/consulta/predio/adeudo/*' => Http::response('<html>respuesta no json</html>', 200),
        ]);

        $predio = new Predio(['clave_predio' => '25A000142060']);

        $resultado = $predio->consultarAdeudo();

        $this->assertSame('no_encontrado', $resultado['estado']);
    }
}
