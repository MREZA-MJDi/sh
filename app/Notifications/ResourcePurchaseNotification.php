<?php

namespace App\Notifications;

use App\Models\ResourcePurchase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class ResourcePurchaseNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ResourcePurchase $purchase,
        private readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->purchase->resource?->title ?? 'خرید محصول آموزشی',
            'message' => $this->message,
            'purchase_id' => $this->purchase->id,
            'url' => $notifiable->hasRole('parent')
                ? route('guardian.resources.index')
                : route('student.resources.index'),
        ];
    }
}
