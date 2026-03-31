<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\AppRelease;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppReleaseNotification extends Notification
{
    use Queueable;

    public function __construct(protected AppRelease $appRelease)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->fcm_token) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Нова версія застосунку',
            'body' => "Доступна версія {$this->appRelease->version_name}.",
            'data' => [
                'changelog' => $this->appRelease->changelog ?? '',
            ],
        ];
    }
}
