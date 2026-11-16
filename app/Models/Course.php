<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'subject', 'grade', 'teacher_id',
        'type', 'price', 'delivery_type', 'status', 'spotplayer_course_id',
        'integration_status', 'integration_note', 'learning_features',
        'teaser_original_path', 'teaser_path', 'teaser_poster_path', 'teaser_status', 'teaser_error',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'learning_features' => 'array',
        ];
    }

    public function getTeaserUrlAttribute(): ?string
    {
        return $this->teaser_path ? Storage::disk('public')->url($this->teaser_path) : null;
    }

    public function getTeaserPosterUrlAttribute(): ?string
    {
        return $this->teaser_poster_path ? Storage::disk('public')->url($this->teaser_poster_path) : null;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class, 'course_id')->orderBy('sort_order')->orderBy('id');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LearningResource::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function spotPlayerLicenses(): HasMany
    {
        return $this->hasMany(SpotPlayerLicense::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function spotPlayerVideos(): HasMany
    {
        return $this->hasMany(SpotPlayerVideo::class);
    }
}
