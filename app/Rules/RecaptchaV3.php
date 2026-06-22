<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class RecaptchaV3 implements ValidationRule
{
    public function __construct(
        protected string $action,
        protected float $scoreThreshold,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            $fail('La verificación de reCAPTCHA es requerida.');

            return;
        }

        $response = Http::asForm()
            ->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha_v3.secret_key'),
                'response' => $value,
            ]);

        $result = $response->json();

        if (! ($result['success'] ?? false)) {
            $fail('La verificación de reCAPTCHA falló. Inténtelo de nuevo.');

            return;
        }

        if (($result['score'] ?? 0) < $this->scoreThreshold) {
            $fail('La verificación de reCAPTCHA detectó actividad sospechosa. Inténtelo de nuevo.');

            return;
        }

        if (($result['action'] ?? '') !== $this->action) {
            $fail('La verificación de reCAPTCHA no coincide con la acción esperada.');
        }
    }
}
