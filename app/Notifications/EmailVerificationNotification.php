<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class EmailVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $code,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SafeDeal — Vérifiez votre email')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Votre code de vérification SafeDeal :')
            ->line("**{$this->code}**")
            ->line('Ce code expire dans 10 minutes.')
            ->line("Si vous n'avez pas créé de compte, ignorez cet email.")
            ->salutation('L\'équipe SafeDeal');
    }

    public function getCode(): string
    {
        return $this->code;
    }
}
