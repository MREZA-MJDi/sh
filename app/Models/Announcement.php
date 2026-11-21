<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use \App\Models\Concerns\SanitizesRichText;

    protected $fillable = ['created_by', 'title', 'body', 'audience', 'status', 'published_at', 'starts_at', 'ends_at', 'priority', 'action_url', 'action_label'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function readers(): BelongsToMany\n    {\n        return $this->belongsToMany(User::class, 'announcement_user_reads')->withPivot('read_at');\n    }\n\n    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
