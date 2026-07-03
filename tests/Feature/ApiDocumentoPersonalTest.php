<?php

namespace Tests\Feature;

use App\Models\catDocumentoPersonal;
use App\Models\tblDocumentoPersonal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiDocumentoPersonalTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-compartido-de-prueba';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.api_token' => self::TOKEN]);

        // El esquema de documentos_personales se gestiona fuera del sistema
        // de migraciones de este repositorio, por lo que se crean las tablas
        // explícitamente para el entorno de pruebas.
        $this->crearEsquemaDocumentosPersonales();

        Storage::fake('local');
    }

    public function test_rechaza_peticion_sin_token(): void
    {
        $documento = $this->crearDocumentoConArchivo();

        $response = $this->getJson("/api/documentos-personales/{$documento->id_documento}/archivo");

        $response->assertStatus(401);
        $response->assertJson(['message' => 'No autorizado.']);
    }

    public function test_rechaza_peticion_con_token_incorrecto(): void
    {
        $documento = $this->crearDocumentoConArchivo();

        $response = $this->withHeaders(['X-Api-Token' => 'token-incorrecto'])
            ->getJson("/api/documentos-personales/{$documento->id_documento}/archivo");

        $response->assertStatus(401);
    }

    public function test_devuelve_el_pdf_inline_con_token_valido(): void
    {
        $documento = $this->crearDocumentoConArchivo();

        $response = $this->withHeaders(['X-Api-Token' => self::TOKEN])
            ->get("/api/documentos-personales/{$documento->id_documento}/archivo");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('INE.pdf', $response->headers->get('Content-Disposition'));
    }

    public function test_devuelve_404_cuando_el_registro_no_tiene_archivo(): void
    {
        $catalogo = catDocumentoPersonal::create([
            'nombre_documento' => 'INE',
            'vigencia_meses' => 12,
            'estatus_documento' => true,
        ]);

        $documento = tblDocumentoPersonal::create([
            'fk_usuario' => User::factory()->create()->id,
            'fk_documento_personal' => $catalogo->id_documento,
            'fecha_registro' => now(),
            'estatus_documento' => tblDocumentoPersonal::ESTATUS_EN_REVISION,
        ]);

        $response = $this->withHeaders(['X-Api-Token' => self::TOKEN])
            ->getJson("/api/documentos-personales/{$documento->id_documento}/archivo");

        $response->assertStatus(404);
    }

    public function test_usa_el_archivo_mas_reciente_cuando_no_hay_ruta_archivo(): void
    {
        // Simula un registro antiguo sin ruta_archivo y con varios PDFs en disco.
        $catalogo = catDocumentoPersonal::create([
            'nombre_documento' => 'INE',
            'vigencia_meses' => 12,
            'estatus_documento' => true,
        ]);

        $usuario = User::factory()->create();

        $documento = tblDocumentoPersonal::create([
            'fk_usuario' => $usuario->id,
            'fk_documento_personal' => $catalogo->id_documento,
            'fecha_registro' => now(),
            'estatus_documento' => tblDocumentoPersonal::ESTATUS_EN_REVISION,
            // ruta_archivo intencionalmente vacío
        ]);

        $directorio = $documento->directorioArchivo();

        // Archivo viejo.
        $rutaViejo = $directorio.'/archivo-viejo.pdf';
        Storage::disk('local')->put($rutaViejo, $this->contenidoPdfValido('viejo'));
        touch(Storage::disk('local')->path($rutaViejo), now()->subHour()->timestamp);

        // Archivo nuevo (debe ser el que se sirva).
        $rutaNuevo = $directorio.'/archivo-nuevo.pdf';
        Storage::disk('local')->put($rutaNuevo, $this->contenidoPdfValido('nuevo'));

        $response = $this->withHeaders(['X-Api-Token' => self::TOKEN])
            ->get("/api/documentos-personales/{$documento->id_documento}/archivo");

        $response->assertOk();
        $this->assertStringContainsString('nuevo', $response->streamedContent());
    }

    /**
     * Crea un documento con su catálogo y un PDF almacenado, registrando
     * la ruta en la columna `ruta_archivo` (flujo de subida actual).
     */
    private function crearDocumentoConArchivo(): tblDocumentoPersonal
    {
        $catalogo = catDocumentoPersonal::create([
            'nombre_documento' => 'INE',
            'vigencia_meses' => 12,
            'estatus_documento' => true,
        ]);

        $usuario = User::factory()->create();

        $documento = tblDocumentoPersonal::create([
            'fk_usuario' => $usuario->id,
            'fk_documento_personal' => $catalogo->id_documento,
            'fecha_registro' => now(),
            'estatus_documento' => tblDocumentoPersonal::ESTATUS_EN_REVISION,
        ]);

        $ruta = $documento->directorioArchivo().'/documento.pdf';
        Storage::disk('local')->put($ruta, $this->contenidoPdfValido());

        $documento->update(['ruta_archivo' => $ruta]);

        return $documento->fresh();
    }

    /**
     * Genera un contenido binario mínimo con la firma mágica %PDF para que
     * Symfony lo detecte como application/pdf en lugar de text/plain.
     */
    private function contenidoPdfValido(string $marca = 'prueba'): string
    {
        return "%PDF-1.4\n%{$marca}\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n".
            "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n".
            "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\n".
            "xref\n0 4\n0000000000 65535 f \n%%EOF\n";
    }

    private function crearEsquemaDocumentosPersonales(): void
    {
        Schema::create('cat_documentos_personales', function ($table) {
            $table->id('id_documento');
            $table->string('nombre_documento');
            $table->integer('vigencia_meses');
            $table->boolean('estatus_documento')->default(true);
            $table->timestamps();
        });

        Schema::create('tbl_documentos_personales', function ($table) {
            $table->id('id_documento');
            $table->unsignedBigInteger('fk_usuario');
            $table->unsignedBigInteger('fk_documento_personal');
            $table->date('fecha_registro');
            $table->integer('estatus_documento')->default(1);
            $table->string('ruta_archivo')->nullable();
            $table->timestamps();
        });
    }
}
