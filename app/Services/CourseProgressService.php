<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CourseProgressService
{
    /**
     * Aggregate only published lessons from courses the student can currently access.
     *
     * @return Collection<int, object>
     */
    public function forStudent(User $student, $courseIds, ?int $limit = null): Collection
    {
        return DB::table('courses')
            ->leftJoin('course_sections', function ($join): void {
                $join->on('course_sections.course_id', '=', 'courses.id')
                    ->where('course_sections.status', '=', 'published');
            })
            ->leftJoin('lessons', function ($join): void {
                $join->on('lessons.course_section_id', '=', 'course_sections.id')
                    ->where('lessons.status', '=', 'published');
            })
            ->leftJoin('lesson_progress', function ($join) use ($student): void {
                $join->on('lesson_progress.lesson_id', '=', 'lessons.id')
                    ->where('lesson_progress.student_id', '=', $student->id);
            })
            ->whereIn('courses.id', $courseIds instanceof \Illuminate\Database\Eloquent\Builder ? clone $courseIds : $courseIds)
            ->where('courses.status', 'published')
            ->groupBy('courses.id', 'courses.title', 'courses.slug')
            ->orderBy('courses.title')
            ->when($limit !== null, fn ($query) => $query->limit(max(1, $limit)))
            ->select([
                'courses.id',
                'courses.title',
                'courses.slug',
            ])
            ->selectRaw('COUNT(lessons.id) AS total_lessons')
            ->selectRaw('SUM(CASE WHEN lesson_progress.completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_lessons')
            ->selectRaw('CASE WHEN COUNT(lessons.id) = 0 THEN 0 ELSE ROUND(AVG(COALESCE(lesson_progress.progress_percent, 0))) END AS progress_percent')
            ->get();
    }

    public function update(User $student, Course $course, Lesson $lesson, int $percent): LessonProgress
    {
        if ($percent < 0 || $percent > 100) {
            throw ValidationException::withMessages(['progress_percent' => 'درصد پیشرفت باید بین صفر تا صد باشد.']);
        }

        if ($course->status !== 'published' || ! $this->hasAccess($student, $course)) {
            abort(404);
        }

        $lesson = Lesson::query()
            ->whereKey($lesson->id)
            ->where('status', 'published')
            ->whereHas('section', fn ($query) => $query
                ->where('course_id', $course->id)
                ->where('status', 'published'))
            ->firstOrFail();

        $now = now();
        $completed = $percent >= 100;

        return LessonProgress::query()->updateOrCreate(
            ['lesson_id' => $lesson->id, 'student_id' => $student->id],
            [
                'progress_percent' => $percent,
                'started_at' => LessonProgress::query()
                    ->where('lesson_id', $lesson->id)
                    ->where('student_id', $student->id)
                    ->value('started_at') ?? $now,
                'last_viewed_at' => $now,
                'completed_at' => $completed ? $now : null,
            ]
        );
    }

    public function hasAccess(User $student, Course $course): bool
    {
        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
