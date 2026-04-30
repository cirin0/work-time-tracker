<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProfileUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $changes
    )
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Ваш профіль був оновлений')
            ->greeting('Привіт,')
            ->line('Ваш профіль був оновлений адміністратором.');

        if (isset($this->changes['name'])) {
            $message->line('Нове ім\'я: ' . $this->changes['name']);
        }

        if (isset($this->changes['email'])) {
            $message->line('Нова пошта: ' . $this->changes['email']);
        }

        return $message
            ->line('Якщо це були не ви, будь ласка зв\'яжіться з адміністратором.');
    }
}
