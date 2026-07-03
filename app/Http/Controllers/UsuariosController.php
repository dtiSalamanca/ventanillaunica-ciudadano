<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    /**
     * Muestra el formulario para cambiar la contraseña del usuario autenticado.
     */
    public function cambiarContrasena(): View
    {
        return view('perfil.cambiarContrasena');
    }

    /**
     * Actualiza la contraseña del usuario tras validar la contraseña actual.
     * Cierra la sesión y solicita al cliente redirigir al login.
     */
    public function actualizarContrasena(Request $request): JsonResponse
    {
        $usuario = Auth::user();

        $validados = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
                'confirmed',
                'different:current_password',
            ],
        ], [
            'current_password.required' => 'Debes ingresar tu contraseña actual.',
            'password.required' => 'Debes ingresar la nueva contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
            'password.symbols' => 'La contraseña debe incluir al menos un símbolo.',
            'password.different' => 'La nueva contraseña debe ser diferente a la actual.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        if (! Hash::check($validados['current_password'], $usuario->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $usuario->forceFill([
            'password' => $validados['password'],
        ])->save();

        // Cierra la sesión del usuario actual y regenera el token CSRF.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Tu contraseña se actualizó correctamente. Inicia sesión nuevamente.',
            'redirect' => route('login'),
        ]);
    }
}
