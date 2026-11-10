<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpotPlayerVideo extends Model
{
    protected $fillable = [
        'course_id',
        'classroom_id',
        'created_by',
        'title',
        'external_id',
        'embed_url',
        'status',
        'file_path',
        'file_original_name',
        'file_mime_type',
        'file_size_bytes',
        'file_sha256',
        'staged_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'staged_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
