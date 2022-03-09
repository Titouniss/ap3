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

class MailNewBugNotification extends Notification
{
    public $mails;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($module,$type,$description,$user_firstname,$user_lastname,$company_name)
    {
        $this->module = $module;
        $this->type = $type;
        $this->description = $description;
        $this->user_firstname = $user_firstname;
        $this->user_lastname = $user_lastname;
        $this->company_name = $company_name;
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
        return (new MailMessage)
            ->cc(['alexislepage@numidev.fr', 'arlenegautre@numidev.fr', 'sebastiensalido@numidev.fr', 'florentinmerlet@numidev.fr', 'juliannebarbot@numidev.fr'])
            ->subject('Nouveau bug')
            ->greeting('Bonjour,')
            ->line('Un nouveau bug a été remonté par '.$this->user_firstname.' '.$this->user_lastname.' de la société '.$this->company_name)
            ->line('Le module concerné est '.$this->module." et le type d'erreur est : ".$this->type)
            ->line("Voici la description de l'erreur : ")
            ->line($this->description);
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
