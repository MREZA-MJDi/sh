<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    use \App\Models\Concerns\SanitizesRichText;

    protected $fillable = ['created_by', 'title', 'body', 'audience', 'status', 'published_at', 'starts_at', 'ends_at', 'priority', 'action_url', 'action_label', 'display_as_ticket'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'display_as_ticket' => 'boolean',
        ];
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_user_reads')->withPivot('read_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
