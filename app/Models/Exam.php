<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = [
        'classroom_id', 'course_id', 'created_by', 'title', 'description',
        'provider', 'external_id', 'starts_at', 'duration_minutes', 'attempts_allowed', 'show_to_guardians', 'status',
        'integration_status', 'integration_note',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'duration_minutes' => 'integer', 'attempts_allowed' => 'integer', 'show_to_guardians' => 'boolean'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
