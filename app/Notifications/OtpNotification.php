<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OtpNotification extends Notification
{
    public function __construct(
        private readonly string $otp,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de vérification SafeDeal')
            ->line("Votre code de vérification est : **{$this->otp}**")
            ->line("Ce code expire dans 10 minutes.")
            ->line("Si vous n'avez pas demandé ce code, ignorez cet email.");
    }
}