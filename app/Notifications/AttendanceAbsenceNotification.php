<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class AttendanceAbsenceNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $student,
        private readonly string $classroomName,
        private readonly int $absenceCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'گزارش غیبت دانش‌آموز',
            'message' => $this->student->name.' در کلاس '.$this->classroomName.' تاکنون '.$this->absenceCount.' جلسه غیبت داشته است.',
            'student_id' => $this->student->id,
            'classroom' => $this->classroomName,
            'absence_count' => $this->absenceCount,
            'url' => route('guardian.dashboard', ['child' => $this->student->id]),
        ];
    }
}