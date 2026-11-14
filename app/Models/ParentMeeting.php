<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ParentMeeting extends Model
{
    protected $fillable = [
        'classroom_id',
        'course_id',
        'student_id',
        'guardian_id',
        'created_by',
        'responded_by',
        'title',
        'message',
        'preferred_at',
        'starts_at',
        'ends_at',
        'responded_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'preferred_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}