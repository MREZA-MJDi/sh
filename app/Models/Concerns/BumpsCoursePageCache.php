<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Cache;

/** روی CourseSection و CourseLesson استفاده شود تا کش صفحه دوره باطل شود. */
trait BumpsCoursePageCache
{
    public static function bootBumpsCoursePageCache(): void
    {
        static::saved(fn () => Cache::increment('course_pages_v'));
        static::deleted(fn () => Cache::increment('course_pages_v'));
    }
}
