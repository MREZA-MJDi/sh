<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReply extends Model
{
    use \App\Models\Concerns\SanitizesRichText;

    protected $fillable = ['message_id', 'sender_id', 'body'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
