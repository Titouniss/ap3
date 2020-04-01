<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserRegistration extends Notification
{
    use Queueable;

    /**
     * The password reset token
     * @var object
     */
    public $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
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
        $link = url( "/pages/register/".$this->user->register_token."/".$notifiable->getEmailForPasswordReset());
        return (new MailMessage)
                    ->subject('Invitation à rejoindre projetx!') // TODO rename projetx
                    ->greeting('Bonjour '.$notifiable->firstname)
                    ->line('ICI un texte pour dire à l utilisateur de cliquer sur le bouton')  // TODO le texte avant bouton clique
                    ->action('S\'inscrire',  $link)
                    ->line('Merci d\'utiliser projetX'); // TODO rename projetx + modification text potentiellement
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
