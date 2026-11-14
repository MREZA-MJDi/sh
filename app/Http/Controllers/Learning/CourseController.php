<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\Learning\CourseAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CourseController extends Controller
{
    public function show(Request $request, string $slug, CourseAccess $access)
    {
        // بخش عمومی (دوره + سرفصل‌های منتشرشده) کش می‌شود؛ بخش مخصوص کاربر هر بار کوئری سبک جدا می‌گیرد.
        $version = Cache::get('course_pages_v', 0);

        $course = Cache::remember("course_page:{$version}:{$slug}", 600, fn () => Course::published()
            ->where('slug', $slug)
            ->with([
                'sections' => fn ($q) => $q->orderBy('position'),
                'sections.lessons' => fn ($q) => $q->where('status', 'published')->orderBy('position')
                    ->select(['id', 'course_section_id', 'title', 'description', 'duration_seconds', 'is_free_preview', 'position']),
            ])
            ->firstOrFail());

        $user = $request->user();
        $state = $access->state($user, $course);

        $completed = $state['unlocked']
            ? LessonProgress::where('user_id', $user->id)->whereNotNull('completed_at')->pluck('course_lesson_id')->all()
            : [];

        $response = response()->view('courses.show', compact('course', 'state', 'completed'));

        // صفحه کاربر لاگین‌شده شامل کلید لایسنس است؛ هرگز کش نشود.
        return $user ? $response->header('Cache-Control', 'private, no-store') : $response;
    }

    /** برای polling وقتی لایسنس در حال آماده‌سازی است. */
    public function accessStatus(Request $request, string $slug, CourseAccess $access): JsonResponse
    {
        $course = Course::published()->where('slug', $slug)->select(['id', 'external_course_id', 'access_type', 'price'])->firstOrFail();
        $state = $access->state($request->user(), $course);

        return response()->json(['state' => $state['state'], 'unlocked' => $state['unlocked']])
            ->header('Cache-Control', 'no-store');
    }
}
