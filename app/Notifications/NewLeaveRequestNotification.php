<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeaveRequestNotification extends Notification
{
    use Queueable;

    public function __construct(protected LeaveRequest $leaveRequest)
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
            ->subject('Новий запит на відпустку')
            ->line("Працівник {$this->leaveRequest->user->name} подав запит на відпустку.")
            ->line('Тип: ' . $this->leaveRequest->type->value)
            ->line('Дата початку: ' . $this->leaveRequest->start_date->format('d.m.Y'))
            ->line('Дата закінчення: ' . $this->leaveRequest->end_date->format('d.m.Y'))
            ->line('Причина: ' . $this->leaveRequest->reason);
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => 'Новий запит на відпустку',
            'body' => "{$this->leaveRequest->user->name} подав запит на відпустку ({$this->leaveRequest->start_date->format('d.m.Y')} — {$this->leaveRequest->end_date->format('d.m.Y')}).",
            'data' => [
                'type' => 'new_leave_request',
                'leave_request_id' => (string)$this->leaveRequest->id,
                'user_id' => (string)$this->leaveRequest->user_id,
            ],
        ];
    }
}
