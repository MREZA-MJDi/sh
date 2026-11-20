<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;
use Illuminate\\Support\\Facades\\Storage;

final class MediaAsset extends Model
{
    protected $fillable = [
        'course_id', 'lesson_id', 'created_by', 'provider', 'status',
        'source_disk', 'source_path', 'processed_path', 'poster_path',
        'original_name', 'mime_type', 'size_bytes', 'duration_seconds',
        'width', 'height', 'sha256', 'processing_error', 'metadata', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'metadata' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
