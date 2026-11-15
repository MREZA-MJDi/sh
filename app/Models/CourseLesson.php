<?php

namespace App\Models;

use App\Models\Concerns\BumpsCoursePageCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLesson extends Model
{
    use BumpsCoursePageCache;

    protected $fillable = [
        'course_section_id', 'title', 'description', 'duration_seconds',
        'external_video_id', 'is_free_preview', 'status', 'position',
        'video_status', 'video_error', 'video_upload_path',
    ];

    protected $casts = ['is_free_preview' => 'boolean'];

    public function section(): BelongsTo { return $this->belongsTo(CourseSection::class, 'course_section_id'); }
}
