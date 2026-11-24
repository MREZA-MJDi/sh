<?php

namespace App\Notifications;

use App\Models\Consultation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ConsultationRequestNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Consultation $consultation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isParentRequest = $this->consultation->requester?->hasRole('parent') ?? false;

        return [
            'title' => 'درخواست مشاوره جدید: '.$this->consultation->topic,
            'message' => $isParentRequest
                ? 'ولی دانش‌آموز درخواست مشاوره ثبت کرده است.'
                : 'درخواست هماهنگی همراه با تاریخ و ساعت پیشنهادی ثبت شده است.',
            'consultation_id' => $this->consultation->id,
            'status' => $this->consultation->status,
            'url' => $notifiable->hasRole('teacher')
                ? route('teacher.consultations.index')
                : route('admin.consultations.index'),
        ];
    }
}