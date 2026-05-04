<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEmailNotification extends Notification
{
    use Queueable;

    public function __construct(public string $oldEmail)
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
            ->subject('Ваша пошта була змінена')
            ->greeting('Привіт,')
            ->line('Пошта вашого облікового запису була змінена адміністратором.')
            ->line('Попередня пошта: ' . $this->oldEmail)
            ->line('Нова пошта: ' . $notifiable->email)
            ->line('Якщо це були не ви, будь ласка негайно зв\'яжіться з адміністратором.');
    }
}
