<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verifica tu correo electrónico - Ventanilla Única')
                ->greeting('¡Hola, '.$notifiable->name.'!')
                ->line('Gracias por crear tu cuenta en la Ventanilla Única de Salamanca, Guanajuato.')
                ->line('Por favor, haz clic en el siguiente botón para verificar tu dirección de correo electrónico y poder iniciar sesión.')
                ->action('Verificar correo electrónico', $url)
                ->line('Si tú no creaste esta cuenta, puedes ignorar este correo.')
                ->salutation('Saludos, Ventanilla Única - Ciudadano');
        });
    }
}
