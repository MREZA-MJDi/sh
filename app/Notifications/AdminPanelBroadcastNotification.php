<?php

namespace App\\Notifications;

use Illuminate\\Bus\\Queueable;
use Illuminate\\Notifications\\Notification;

final class AdminPanelBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly ?string $url,
        private readonly ?string $actionLabel,
        private readonly string $broadcastId,
        private readonly string $audience,
        private readonly int $senderId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => 'admin_broadcast',
            'broadcast_id' => $this->broadcastId,
            'sender_id' => $this->senderId,
            'audience' => $this->audience,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'action_label' => $this->actionLabel,
        ];
    }
}
