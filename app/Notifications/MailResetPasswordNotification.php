<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Auth\Notifications\ResetPassword;
class MailResetPasswordNotification extends ResetPassword
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        parent::__construct($token);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $link = url( "/pages/reset-password/".$this->token."/".$notifiable->getEmailForPasswordReset() );
        return ( new MailMessage )
            ->subject( 'Réinitialisation mot de passe' )
            ->greeting('Bonjour !')
            ->line( "Vous recevez cet email car nous avons reçu une demande de changement de mot de passe." )
            ->action('Réinitialiser', $link)
            ->line( "Ce lien expire dans ".config('auth.passwords.users.expire')." minutes" )
            ->line( "Ignorer ce message si vous n'avez pas fait de demande." );
        }
    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
