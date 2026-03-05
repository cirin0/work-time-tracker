<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Enums\LeaveRequestStatus;
use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestStatusNotification extends Notification
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
        $isApproved = $this->leaveRequest->status === LeaveRequestStatus::APPROVED;
        $statusText = $isApproved ? 'схвалено' : 'відхилено';
        $subject = $isApproved ? 'Заявку на відпустку схвалено' : 'Заявку на відпустку відхилено';

        $mail = (new MailMessage)
            ->subject($subject)
            ->line("Вашу заявку на відпустку було {$statusText}.")
            ->line('Тип: ' . $this->leaveRequest->type->value)
            ->line('Дата початку: ' . $this->leaveRequest->start_date)
            ->line('Дата закінчення: ' . $this->leaveRequest->end_date);

        if (!$isApproved && $this->leaveRequest->manager_comment) {
            $mail->line('Коментар менеджера: ' . $this->leaveRequest->manager_comment);
        }

        return $mail;
    }

    public function toFcm(object $notifiable): array
    {
        $isApproved = $this->leaveRequest->status === LeaveRequestStatus::APPROVED;

        return [
            'title' => $isApproved ? 'Заявку схвалено' : 'Заявку відхилено',
            'body' => $isApproved
                ? "Ваша заявка на відпустку ({$this->leaveRequest->start_date} — {$this->leaveRequest->end_date}) схвалена."
                : "Ваша заявка на відпустку відхилена. " . ($this->leaveRequest->manager_comment ?? ''),
            'data' => [
                'type' => 'leave_request_status',
                'leave_request_id' => (string)$this->leaveRequest->id,
                'status' => $this->leaveRequest->status->value,
            ],
        ];
    }
}
