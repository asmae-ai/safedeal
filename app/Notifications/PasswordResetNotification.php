<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SafeDeal — Reset your password')
            ->greeting("Hello {$notifiable->name},")
            ->line('You requested a password reset.')
            ->line("Your reset token: **{$this->token}**")
            ->line('Valid for 60 minutes.')
            ->salutation('The SafeDeal Team');
    }
}
