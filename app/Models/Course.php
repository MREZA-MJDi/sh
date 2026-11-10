<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'subject', 'grade', 'teacher_id',
        'type', 'price', 'delivery_type', 'status', 'spotplayer_course_id',
        'integration_status', 'integration_note',
    ];

    protected function casts(): array
    {
        return ['price' => 'integer'];
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
}
