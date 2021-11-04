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

class MailActiveSubscriptionNotification extends Notification
{
    public $mails;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($admin_firstname, $admin_lastname, $start_date_subscription, $end_date_subscription)
    {
        $this->admin_firstname = $admin_firstname;
        $this->admin_lastname = $admin_lastname;
        $this->start_date_subscription = Carbon::createFromFormat('Y-m-d H:i:s',$start_date_subscription)->format('d/m/Y');
        $this->end_date_subscription = Carbon::createFromFormat('Y-m-d H:i:s',$end_date_subscription)->format('d/m/Y');
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
            ->subject('Plannigo Abonnement activé')
            ->greeting('Bonjour '.$this->admin_firstname.' '.$this->admin_lastname.',')
            ->line("Votre abonnement Plannigo vient d'être activé.")
            ->line('La date de début est le '.$this->start_date_subscription.' et la date de fin est le '.$this->end_date_subscription.'.');
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
