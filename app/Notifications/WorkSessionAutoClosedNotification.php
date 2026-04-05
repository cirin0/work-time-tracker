<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\TimeEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkSessionAutoClosedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected TimeEntry $timeEntry
    ) {}

    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): array
    {
        $startTime = $this->timeEntry->start_time->format('H:i');
        $stopTime = $this->timeEntry->stop_time->format('H:i');

        return [
            'title' => '🔒 Зміна автоматично закрита',
            'body' => "Ваша робоча зміна ({$startTime} - {$stopTime}) була автоматично закрита системою після 5 годин роботи.",
            'data' => [
                'type' => 'work_session_auto_closed',
                'time_entry_id' => $this->timeEntry->id,
                'start_time' => $this->timeEntry->start_time->toIso8601String(),
                'stop_time' => $this->timeEntry->stop_time->toIso8601String(),
            ],
        ];
    }
}
