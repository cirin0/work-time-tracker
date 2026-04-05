<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\TimeEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkSessionAutoCloseWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected TimeEntry $timeEntry,
        protected int $minutesRemaining
    ) {}

    public function via($notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): array
    {
        $hours = floor($this->minutesRemaining / 60);
        $minutes = $this->minutesRemaining % 60;
        $timeText = $hours > 0 ? "{$hours}г {$minutes}хв" : "{$minutes}хв";

        return [
            'title' => '⏰ Попередження про автозакриття',
            'body' => "Ваша робоча зміна буде автоматично закрита через {$timeText}. Будь ласка, завершіть роботу вручну.",
            'data' => [
                'type' => 'work_session_auto_close_warning',
                'time_entry_id' => $this->timeEntry->id,
                'minutes_remaining' => $this->minutesRemaining,
            ],
        ];
    }
}
