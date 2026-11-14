<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLesson;
use App\Models\LessonProgress;
use App\Services\Learning\CourseAccess;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function show(Request $request, string $slug, CourseLesson $lesson, CourseAccess $access)
    {
        [$course, $flat] = $this->resolve($slug, $lesson);
        $state = $access->state($request->user(), $course);

        abort_unless($access->canViewLesson($request->user(), $course, $lesson), 403);

        $index = $flat->search(fn ($l) => $l->id === $lesson->id);
        $prev = $index > 0 ? $flat[$index - 1] : null;
        $next = $flat[$index + 1] ?? null;

        $embedUrl = null;
        if (config('learning.player_mode') === 'embed'
            && ($tpl = config('learning.embed_url_template'))
            && $lesson->external_video_id
            && $state['state'] === CourseAccess::ACTIVE) {
            $embedUrl = str_replace('{video_id}', rawurlencode($lesson->external_video_id), $tpl);
        }

        $done = LessonProgress::where('user_id', $request->user()->id)
            ->where('course_lesson_id', $lesson->id)->whereNotNull('completed_at')->exists();

        return response()
            ->view('courses.lesson', compact('course', 'lesson', 'state', 'prev', 'next', 'embedUrl', 'done', 'index') + ['total' => $flat->count()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function toggleComplete(Request $request, string $slug, CourseLesson $lesson, CourseAccess $access)
    {
        [$course] = $this->resolve($slug, $lesson);
        abort_unless($access->canViewLesson($request->user(), $course, $lesson), 403);

        $progress = LessonProgress::firstOrNew(['user_id' => $request->user()->id, 'course_lesson_id' => $lesson->id]);
        $progress->completed_at = $progress->completed_at ? null : now();
        $progress->save();

        return back();
    }

    /** مطمئن می‌شود درس واقعاً مال همین دوره است و لیست مسطح درس‌ها را برمی‌گرداند. */
    private function resolve(string $slug, CourseLesson $lesson): array
    {
        $course = Course::published()->where('slug', $slug)->firstOrFail();

        $course->load(['sections' => fn ($q) => $q->orderBy('position'),
            'sections.lessons' => fn ($q) => $q->where('status', 'published')->orderBy('position')->select(['id', 'course_section_id', 'title'])]);

        $flat = $course->sections->flatMap->lessons->values();
        abort_unless($flat->contains(fn ($l) => $l->id === $lesson->id), 404);

        return [$course, $flat];
    }
}
