<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

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
                ->line('Gracias por registrarte en la **Ventanilla Única de Salamanca, Guanajuato**.')
                ->line('Para completar tu registro, verifica tu correo electrónico haciendo clic en el siguiente botón:')
                ->action('Verificar correo electrónico', $url)
                ->line('Una vez verificado tu correo, podrás iniciar sesión en la Ventanilla Única.')
                ->line('Si tú no creaste esta cuenta, puedes ignorar este correo.')
                ->salutation('Saludos cordiales,<br>**Ventanilla Única**<br>**H. Ayuntamiento de Salamanca, Guanajuato**')
                ->withSymfonyMessage(fn (Email $message) => $this->embedLogo($message));
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Restablece tu contraseña - Ventanilla Única')
                ->greeting('¡Hola, '.$notifiable->name.'!')
                ->line('Recibes este correo porque se recibió una solicitud para restablecer la contraseña de tu cuenta en la **Ventanilla Única de Salamanca, Guanajuato**.')
                ->action('Restablecer contraseña', $resetUrl)
                ->line('Este enlace de restablecimiento de contraseña expirará en '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutos.')
                ->line('Si no solicitaste restablecer tu contraseña, no es necesario que realices ninguna acción.')
                ->salutation('Saludos cordiales,<br>**Ventanilla Única**<br>**H. Ayuntamiento de Salamanca, Guanajuato**')
                ->withSymfonyMessage(fn (Email $message) => $this->embedLogo($message));
        });
    }

    /**
     * Incrusta el escudo de Salamanca como imagen inline (CID) en el correo,
     * sin que Gmail lo muestre como archivo adjunto.
     *
     * @param  Email  $message  Mensaje Symfony recibido por withSymfonyMessage()
     */
    protected function embedLogo(Email $message): void
    {
        $path = public_path('images/escudoArma.png');

        if (! is_file($path)) {
            return;
        }

        // Se pasa el contenido como string y nombre null para que el DataPart
        // no defina filename; setName('') evita el "name=" en el MIME.
        $part = new DataPart((string) file_get_contents($path), null, 'image/png');
        $part->setContentId('escudo@salamanca.gob.mx');
        $part->setName('');

        $message->addPart($part);
    }
}
