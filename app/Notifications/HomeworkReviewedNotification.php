<?php

namespace App\Notifications;

use App\Models\HomeworkSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class HomeworkReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly HomeworkSubmission $submission) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->submission->loadMissing(['homework', 'student']);

        return [
            'title' => 'نتیجه بررسی تکلیف: '.($this->submission->homework?->title ?? 'تکلیف'),
            'message' => filled($this->submission->feedback)
                ? $this->submission->feedback
                : 'مدرس تکلیف شما را بررسی کرد.',
            'score' => $this->submission->score,
            'submission_id' => $this->submission->id,
            'url' => $notifiable->hasRole('parent')
                ? route('guardian.homework')
                : route('student.homework.index'),
        ];
    }
}
