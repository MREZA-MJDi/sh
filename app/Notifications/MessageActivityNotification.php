<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class MessageActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Message $message,
        private readonly string $summary,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->message->subject,
            'message' => $this->summary,
            'message_id' => $this->message->id,
            'url' => $notifiable->hasRole('teacher')
                ? route('admin.messages.index', ['message' => $this->message->id])
                : ($notifiable->hasRole('parent')
                    ? route('guardian.messages.show', $this->message->id)
                    : ($notifiable->hasRole('student')
                        ? route('student.messages.show', $this->message->id)
                        : route('admin.messages.index', ['message' => $this->message->id]))),
        ];
    }
}