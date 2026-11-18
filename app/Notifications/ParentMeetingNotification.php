<?php

namespace App\Notifications;

use App\Models\ParentMeeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ParentMeetingNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ParentMeeting $meeting,
        private readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->meeting->title,
            'message' => $this->message,
            'meeting_id' => $this->meeting->id,
            'status' => $this->meeting->status,
            'url' => $notifiable->hasRole('parent')
                ? route('guardian.meetings.index')
                : route('teacher.meetings'),
        ];
    }
}