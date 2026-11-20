<?php

namespace App\Notifications;

use App\Models\SpotPlayerVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class CourseVideoSubmittedForReview extends Notification
{
    use Queueable;

    public function __construct(private readonly SpotPlayerVideo $video) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'course_video_review',
            'title' => 'ویدیوی جدید برای تأیید',
            'message' => 'مدرس «'.($this->video->creator?->name ?? 'مدرس'). '» ویدیوی «'.$this->video->title.'» را برای بررسی ارسال کرده است.',
            'video_id' => $this->video->id,
            'course_id' => $this->video->course_id,
            'url' => route('admin.spotplayer.index'),
        ];
    }
}
