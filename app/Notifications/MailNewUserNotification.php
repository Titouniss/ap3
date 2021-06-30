<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;

class MailNewUserNotification extends Notification
{

     /**
     * The user id
     * @var string
     */
    public $firstname;
    public $lastname;
    public $email;
    public $company_name;
    public $contact_tel1;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($firstname, $lastname, $email, $company_name, $contact_tel1)
    {
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
        $this->company_name = $company_name;
        $this->contact_tel1 = $contact_tel1;
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
            ->subject('Inscription nouvelle société !')
            ->cc('arlenegautre@numidev.fr')
            ->greeting('Bonjour,')
            ->line("La société ".$this->company_name." vient de s'inscrire à l'application Plannigo.")  // TODO le texte avant bouton clique
            ->line("L'utilisateur est : ".$this->firstname." ".$this->lastname)
            ->line("L'adresse e-mail est : ".$this->email." et le numéro de téléphone est : ".$this->contact_tel1);
    }

    /**
     * Set a callback that should be used when building the notification mail message.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function toMailUsing($callback)
    {
        static::$toMailCallback = $callback;
    }
}
