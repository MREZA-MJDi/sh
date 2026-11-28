<?php

namespace App\Jobs;

use App\Models\AdminNotificationBroadcast;
use App\Notifications\AdminPanelBroadcastNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class SendAdminNotificationBroadcast implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $broadcastId) {}

    public function handle(): void
    {
        while (DB::transaction(fn () => $this->sendBatch())) {
            // Each committed batch advances the cursor for worker retries.
        }
    }

    private function sendBatch(): bool
    {
        $broadcast = AdminNotificationBroadcast::whereKey($this->broadcastId)->lockForUpdate()->first();
        if (! $broadcast || $broadcast->delivery_status === 'sent') {
            return false;
        }

        $recipients = AdminNotificationBroadcast::recipients($broadcast->audience)
            ->where('id', '>', $broadcast->recipient_cursor)
            ->where('id', '<=', $broadcast->recipient_max_id)
            ->orderBy('id')->limit(200)->get(['id']);

        if ($recipients->isEmpty()) {
            $broadcast->delivery_status = 'sent';
            $broadcast->save();

            return false;
        }

        Notification::sendNow($recipients, new AdminPanelBroadcastNotification(
            $broadcast->title,
            $broadcast->message,
            $broadcast->url,
            $broadcast->action_label,
            $broadcast->id,
            $broadcast->audience,
            (int) $broadcast->sender_id,
        ));

        $broadcast->delivery_status = 'sending';
        $broadcast->recipient_cursor = $recipients->last()->id;
        $broadcast->delivered_count += $recipients->count();
        $broadcast->save();

        return true;
    }

    public function failed(?Throwable $exception): void
    {
        AdminNotificationBroadcast::whereKey($this->broadcastId)
            ->where('delivery_status', '!=', 'sent')
            ->update(['delivery_status' => 'failed']);
    }
}
