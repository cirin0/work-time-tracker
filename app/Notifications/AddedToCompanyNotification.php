<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddedToCompanyNotification extends Notification
{

    use Queueable;

    public function __construct(public Company $company)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable->fcm_token) {
            $channels[] = FcmChannel::class;
        }
        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject('Ви додані до компанії')
            ->line("Вас додано до компанії {$this->company->name}.")
            ->line('Тепер ви можете користуватися всіма функціями системи.');
    }

    public function toFcm($notifiable): array
    {
        return [
            'title' => 'Ви додані до компанії',
            'body' => "Вас додано до компанії {$this->company->name}. Тепер ви можете користуватися всіма функціями системи.",
        ];
    }
}
