<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateLessonProgressRequest;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\SpotPlayerLicense;
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

    public function show(Request $request, int $course): RedirectResponse
    {
        $course = Course::query()
            ->whereKey($course)
            ->whereIn('id', $this->activeCourseIds($request))
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

    private function activeCourseIds(Request $request)
    {
        return CourseEnrollment::query()
            ->where('student_id', $request->user()->id)
            ->currentlyActive()
            ->whereHas('course', fn ($course) => $course->where('status', 'published'))
            ->select('course_id');
    }
}
