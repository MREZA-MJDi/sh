<?php

namespace App\Services\Messaging;

use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use Illuminate\Support\Facades\DB;

final class MessageReplyService
{
    public function reply(Message $authorizedMessage, User $sender, string $body, bool $markAnswered = false): void
    {
        $message = DB::transaction(function () use ($authorizedMessage, $sender, $body, $markAnswered): Message {
            $message = Message::query()->lockForUpdate()->findOrFail($authorizedMessage->id);

            abort_unless($message->acceptsReplies(), 422, 'گفت‌وگوی بسته‌شده قابل پاسخ‌گویی نیست.');

            $message->replies()->create(['sender_id' => $sender->id, 'body' => $body]);
            $message->update([
                'status' => $markAnswered ? 'answered' : $message->status,
                'replied_at' => now(),
            ]);

            return $message;
        }, 3);

        $participant = (int) $message->sender_id === (int) $sender->id
            ? $message->recipient_id
            : $message->sender_id;

        User::query()->find($participant)?->notify(
            new MessageActivityNotification($message, 'پاسخ جدیدی در گفت‌وگوی شما ثبت شد.')
        );
    }
}
