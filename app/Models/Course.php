<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Course extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'cover_image_path', 'subject', 'grade', 'academic_grade_id', 'season', 'teacher_id',
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

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function getTeaserUrlAttribute(): ?string
    {
        return $this->teaser_path ? Storage::disk('public')->url($this->teaser_path) : null;
    }

    public function getTeaserPosterUrlAttribute(): ?string
    {
        return $this->teaser_poster_path ? Storage::disk('public')->url($this->teaser_poster_path) : null;
    }

    public function academicGrade(): BelongsTo
    {
        return $this->belongsTo(AcademicGrade::class, 'academic_grade_id');
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

    /**
     * Backward-compatible feature policy: explicit matrix values always win;
     * legacy courses retain their former delivery defaults until configured.
     */
    public function featureEnabled(string $feature): bool
    {
        $features = $this->learning_features;

        if (is_array($features) && array_key_exists($feature, $features)) {
            return (bool) $features[$feature];
        }

        return match ($feature) {
            'recorded_video' => $this->delivery_type === 'spotplayer',
            'live_class' => $this->delivery_type === 'skyroom',
            'resources', 'assignments', 'exams' => true,
            'projects' => false,
            default => false,
        };
    }
}
