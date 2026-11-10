<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = ['sender_id', 'recipient_id', 'parent_id', 'subject', 'body', 'status', 'replied_at'];

    protected function casts(): array
    {
        return ['replied_at' => 'datetime'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(MessageReply::class)->with('sender')->oldest();
    }
}
