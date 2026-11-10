<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateLessonProgressRequest;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Services\CourseProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CourseController extends Controller
{
    public function index(Request $request, CourseProgressService $progress): View
    {
        $courseIds = $this->activeCourseIds($request);
        $courses = Course::query()
            ->whereIn('id', clone $courseIds)
            ->where('status', 'published')
            ->with('teacher:id,name')
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $pageCourseIds = $courses->getCollection()->pluck('id')->all();
        $progressByCourse = $progress->forStudent($request->user(), $pageCourseIds)->keyBy('id');

        return view('student.courses.index', compact('courses', 'progressByCourse'));
    }

    public function show(Request $request, int $course): View
    {
        $courseIds = $this->activeCourseIds($request);
        $course = Course::query()
            ->whereKey($course)
            ->whereIn('id', clone $courseIds)
            ->where('status', 'published')
            ->with('teacher:id,name')
            ->firstOrFail();

        $sections = $course->sections()
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10, ['*'], 'sections_page')
            ->withQueryString();

        $selectedSection = $request->filled('section')
            ? $course->sections()
                ->whereKey($request->integer('section'))
                ->where('status', 'published')
                ->first()
            : null;

        $selectedSection ??= $sections->first();

        $lessons = $selectedSection
            ? $selectedSection->lessons()
                ->where('status', 'published')
                ->with(['progress' => fn ($progress) => $progress->where('student_id', $request->user()->id)])
                ->paginate(30, ['*'], 'lessons_page')
                ->withQueryString()
            : null;

        return view('student.courses.show', compact('course', 'sections', 'selectedSection', 'lessons'));
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

    private function activeCourseIds(Request $request)
    {
        return CourseEnrollment::query()
            ->where('student_id', $request->user()->id)
            ->where('status', 'active')
            ->where(fn ($expiry) => $expiry->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->whereHas('course', fn ($course) => $course->where('status', 'published'))
            ->select('course_id');
    }
}
