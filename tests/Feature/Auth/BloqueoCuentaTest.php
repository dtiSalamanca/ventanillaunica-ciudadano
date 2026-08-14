<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BloqueoCuentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_bloqueado_no_puede_iniciar_sesion(): void
    {
        User::factory()->create([
            'email' => 'bloqueado@example.com',
            'email_verified_at' => now(),
            'bloqueado' => true,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'bloqueado@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors([
                'email' => 'Tu cuenta ha sido bloqueada. Ponte en contacto con la Ventanilla Única de Salamanca para más información.',
            ]);

        $this->assertGuest();
    }

    public function test_usuario_activo_si_puede_iniciar_sesion(): void
    {
        User::factory()->create([
            'email' => 'activo@example.com',
            'email_verified_at' => now(),
            'bloqueado' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'activo@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');

        $this->assertAuthenticated();
    }
}
