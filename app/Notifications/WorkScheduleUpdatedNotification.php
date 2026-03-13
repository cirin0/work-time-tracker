<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\WorkSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkScheduleUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(protected WorkSchedule $workSchedule)
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ваш робочий графік оновлено')
            ->line('Менеджер змінив ваш робочий графік.')
            ->line('Новий графік: ' . $this->workSchedule->name)
            ->line('Будь ласка, ознайомтеся з новим розкладом у застосунку.');
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Графік роботи змінено',
            'body' => "Ваш робочий графік оновлено на \"{$this->workSchedule->name}\".",
            'data' => [
                'type' => 'work_schedule_updated',
                'work_schedule_id' => (string)$this->workSchedule->id,
            ],
        ];
    }
}
