<?php

namespace App\Models;

use App\Models\Concerns\BumpsCoursePageCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSection extends Model
{
    use BumpsCoursePageCache;

    protected $fillable = ['course_id', 'title', 'position'];

    public function course(): BelongsTo { return $this->belongsTo(Course::class); }

    public function lessons(): HasMany { return $this->hasMany(CourseLesson::class)->orderBy('position'); }
}
