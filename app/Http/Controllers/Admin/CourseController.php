<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\AcademicGrade;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Courses\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CourseController extends Controller
{
    public function create(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('courses.manage'), 403);

        $teachers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $grades = AcademicGrade::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'code']);

        return view('admin.courses.form', [
            'grades' => $grades,
            'course' => new Course(['type' => 'free', 'price' => 0, 'delivery_type' => 'onsite', 'status' => 'draft', 'learning_features' => ['recorded_video' => false, 'live_class' => false, 'resources' => false, 'assignments' => false, 'exams' => false, 'projects' => false]]),
            'teachers' => $teachers,
            'isEditing' => false,
            'videos' => collect(),
            'classrooms' => collect(),
        ]);
    }

    public function edit(Course $course): View
    {
        abort_unless(auth()->user()->hasPermissionTo('courses.manage'), 403);

        $teachers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $grades = AcademicGrade::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'code']);
        $videos = $course->spotPlayerVideos()->with(['classroom:id,name', 'classSession:id,classroom_id,title,starts_at'])->latest()->paginate(10, ['*'], 'videos');
        $classrooms = $course->classrooms()
            ->where('status', 'active')
            ->with(['sessions' => fn ($sessions) => $sessions->where('status', '!=', 'cancelled')->orderByDesc('starts_at')])
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'course_id']);

        return view('admin.courses.form', compact('course', 'teachers', 'videos', 'classrooms', 'grades') + ['isEditing' => true]);
    }

    public function index(ClassroomWorkspaceService $workspace): View
    {
        $courses = $workspace->coursesQueryFor(auth()->user())
            ->with('teacher')
            ->withCount(['sections'])
            ->withCount([
                'classrooms as active_classrooms_count' => fn ($classrooms) => $classrooms
                    ->where('status', 'active'),
                'enrollments as active_enrollments_count' => fn ($enrollments) => $enrollments
                    ->currentlyActive(),
                'enrollments as pending_payment_enrollments_count' => fn ($enrollments) => $enrollments
                    ->where('status', 'pending_payment'),
            ])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $user = auth()->user();
        $grades = AcademicGrade::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'name')->all();

        $visibleCourseIds = $workspace->coursesQueryFor($user)->select('courses.id');
        $stats = [
            'courses' => (clone $visibleCourseIds)->count(),
            'published' => Course::query()->whereIn('id', clone $visibleCourseIds)->where('status', 'published')->count(),
            'students' => CourseEnrollment::query()
                ->whereIn('course_id', clone $visibleCourseIds)
                ->currentlyActive()
                ->distinct('student_id')
                ->count('student_id'),
            'lessons' => Lesson::query()->where('status', 'published')->whereHas('section', fn ($sections) => $sections
                ->whereIn('course_id', clone $visibleCourseIds))->count(),
            'drafts' => Course::query()->whereIn('id', clone $visibleCourseIds)->where('status', 'draft')->count(),
        ];
        $canManage = $user->hasPermissionTo('courses.manage');
        $canManageContent = $user->hasAnyPermission(['courses.manage', 'courses.content.manage.assigned']);

        return view('admin.courses.index', compact('courses', 'stats', 'canManage', 'canManageContent', 'grades'));
    }

    public function store(StoreCourseRequest $request, CourseService $courses): RedirectResponse
    {
        $course = $courses->create($request->validated());

        $message = in_array($course->integration_status, ['waiting_course_mapping', 'awaiting_api_verification'], true)
            ? 'دوره در شیخان ایجاد شد؛ اتصال SpotPlayer در کارتابل یکپارچه‌سازی در انتظار قرارداد وب‌سرویس است.'
            : 'دوره با موفقیت ایجاد شد.';

        return redirect()->route('admin.courses.edit', $course)->with('success', $message);
    }

    public function update(
        UpdateCourseRequest $request,
        Course $course,
        CourseService $courses
    ): RedirectResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $course = $courses->update($course, $request->validated());

        $message = in_array($course->integration_status, ['waiting_course_mapping', 'awaiting_api_verification'], true)
            ? 'اطلاعات دوره ذخیره شد؛ وضعیت SpotPlayer تا زمان تأیید API در انتظار باقی می‌ماند.'
            : 'دوره به‌روزرسانی شد.';

        return redirect()->route('admin.courses.edit', $course)->with('success', $message);
    }

    public function destroy(
        \Illuminate\Http\Request $request,
        Course $course,
        CourseService $courses
    ): RedirectResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $courses->delete($course);

        return back()->with('success', 'دوره حذف شد.');
    }
}
