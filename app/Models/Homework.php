<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Homework extends Model
{
    use \App\Models\Concerns\SanitizesRichText;

    protected $table = 'homeworks';

    protected $fillable = ['classroom_id', 'title', 'instructions', 'due_at', 'attachment_path', 'status', 'created_by'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HomeworkSubmission::class);
    }
}
