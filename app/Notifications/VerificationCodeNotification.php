<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $code, protected string $type)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Код для {$this->type}")
            ->line("Ви отримали це повідомлення, тому що ми отримали запит на {$this->type} для вашого акаунту.")
            ->line("Ваш 6-значний код:")
            ->line($this->code)
            ->line("Цей код дійсний протягом 15 хвилин.");
    }
}
