<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use \App\Models\Concerns\SanitizesRichText;

    protected $fillable = [
        'sender_id', 'recipient_id', 'student_id', 'parent_id',
        'subject', 'body', 'status', 'replied_at',
    ];

    protected function casts(): array
    {
        return ['replied_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function conversationStatus(): string
    {
        return $this->closed_at !== null && $this->status !== 'deleted' ? 'closed' : $this->status;
    }

    public function acceptsReplies(): bool
    {
        return ! in_array($this->conversationStatus(), ['closed', 'deleted'], true);
    }

    public function scopeWithConversationStatus(Builder $query, string $status): Builder
    {
        return match ($status) {
            'closed' => $query->where(fn (Builder $closed) => $closed->where('status', 'closed')
                ->orWhere(fn (Builder $marked) => $marked->whereNotNull('closed_at')->where('status', '!=', 'deleted'))),
            'deleted' => $query->where('status', 'deleted'),
            default => $query->whereNull('closed_at')->where('status', $status),
        };
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(MessageReply::class)->with('sender')->oldest();
    }
}
