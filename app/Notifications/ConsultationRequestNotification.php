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
        return [
            'title' => 'درخواست جدید مدرس: '.$this->consultation->topic,
            'message' => 'درخواست هماهنگی همراه با تاریخ و ساعت پیشنهادی در کارتابل مشاوره ثبت شده است.',
            'consultation_id' => $this->consultation->id,
            'status' => $this->consultation->status,
            'url' => route('admin.consultations.index'),
        ];
    }
}