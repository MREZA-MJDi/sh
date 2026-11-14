<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LearningResourceAccessLog extends Model
{
    protected $fillable = [
        'learning_resource_id',
        'user_id',
        'student_id',
        'event_type',
        'ip_address',
        'user_agent',
        'accessed_at',
    ];

    protected function casts(): array
    {
        return ['accessed_at' => 'datetime'];
    }

    public function resource(): BelongsTo
    {
        return $this->belongsTo(LearningResource::class, 'learning_resource_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}