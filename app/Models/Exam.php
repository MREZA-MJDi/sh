<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = ['classroom_id', 'course_id', 'created_by', 'title', 'description', 'provider', 'external_id', 'starts_at', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
