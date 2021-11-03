<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class MailRegistrationLinkNotification extends Notification
{
    public $mails;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($mailsToSend, $companyName)
    {
        $this->mailsToSend = $mailsToSend;
        $this->companyName = $companyName;
    }
    /**
     * The callback that should be used to build the mail message.
     *
     * @var \Closure|null
     */
    public static $toMailCallback;

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $link = "app.plannigo.fr/pages/register?company=".$this->companyName;
        return (new MailMessage)
            ->subject(Lang::get("Lien d'inscription Plannigo"))
            ->cc($this->mailsToSend)
            ->greeting('Bonjour,')
            ->line("Voici votre lien d'inscription :")
            ->line(Lang::get("Cliquez sur le lien ci-dessous afin d'aller sur le formulaire d'inscription."))
            ->action(Lang::get("Lien d'inscription"), $link)
            ->line(Lang::get('Si vous n\'avez pas fait de demande ignorer ce message.'));
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
