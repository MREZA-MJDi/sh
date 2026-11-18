<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateLessonProgressRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\CourseProgressService;
use App\Services\Learning\LearningAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CourseController extends Controller
{
    public function index(Request $request, CourseProgressService $progress, LearningAccessService $access): View
    {
        $student = $request->user();
        $activeCourseIds = $access->activeCourseIds($student);

        // Discovery is separate from learning access: every published free
        // course is visible, while paid courses remain visible only to enrolled students.
        $courses = Course::query()
            ->where('status', 'published')
            ->where(function ($query) use ($activeCourseIds): void {
                $query->where('type', 'free')
                    ->orWhereIn('id', clone $activeCourseIds);
            })
            ->with('teacher:id,name')
            ->withExists(['enrollments as has_active_enrollment' => fn ($enrollments) => $enrollments
                ->where('student_id', $student->id)
                ->currentlyActive()])
            ->orderByRaw("CASE WHEN type = 'free' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $enrolledCourseIds = $courses->getCollection()
            ->filter(fn (Course $course): bool => (bool) $course->has_active_enrollment)
            ->pluck('id')
            ->all();
        $progressByCourse = $progress->forStudent($student, $enrolledCourseIds)->keyBy('id');

        return view('student.courses', compact('courses', 'progressByCourse'));
    }

    public function show(Request $request, int $course, LearningAccessService $access): RedirectResponse
    {
        $course = Course::query()
            ->whereKey($course)
            ->whereIn('id', $access->activeCourseIds($request->user()))
            ->where('status', 'published')
            ->firstOrFail();

        return redirect()->route('public.courses.show', $course->slug);
    }

    public function updateProgress(
        UpdateLessonProgressRequest $request,
        int $course,
        Lesson $lesson,
        CourseProgressService $progress
    ): RedirectResponse {
        $courseModel = Course::query()->findOrFail($course);
        $progress->update(
            $request->user(),
            $courseModel,
            $lesson,
            (int) $request->validated('progress_percent')
        );

        return back()->with('success', 'پیشرفت درس ثبت شد.');
    }

}
