<?php

namespace App\Channels;

use Exception;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmChannel
{
    public function __construct(
        protected Messaging $messaging
    )
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $token = $notifiable->fcm_token ?? null;

        if (!$token) {
            return;
        }

        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);

        try {
            $message = CloudMessage::new()
                ->withToken($token)
                ->withNotification(FcmNotification::create(
                    $payload['title'],
                    $payload['body']
                ))
                ->withData(array_map(fn($value) => (string)$value, $payload['data'] ?? []));

            $this->messaging->send($message);

        } catch (Exception $e) {
            Log::error('FCM notification failed (kreait)', [
                'error' => $e->getMessage(),
                'user_id' => $notifiable->id ?? null,
            ]);
        }
    }
}
