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
        $courseIds = $access->activeCourseIds($request->user());
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
