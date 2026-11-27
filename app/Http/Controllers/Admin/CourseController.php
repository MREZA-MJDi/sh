<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\AcademicGrade;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LearningResource;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Courses\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class CourseController extends Controller
{
    public function create(): View
    {
        abort_unless(auth()->user()->hasRole('admin') && auth()->user()->hasPermissionTo('courses.manage'), 403);

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
        abort_unless(auth()->user()->hasRole('admin') && auth()->user()->hasPermissionTo('courses.manage'), 403);

        $teachers = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $grades = AcademicGrade::query()->where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'code']);
        $course->load([
            'sections' => fn ($sections) => $sections->orderBy('sort_order')->orderBy('id')
                ->with(['lessons' => fn ($lessons) => $lessons->orderBy('sort_order')->orderBy('id')]),
        ]);
        $videos = $course->spotPlayerVideos()->with(['classroom:id,name', 'classSession:id,classroom_id,title,starts_at'])->latest()->paginate(10, ['*'], 'videos');
        $classrooms = $course->classrooms()
            ->where('status', 'active')
            ->with(['teacher:id,name', 'sessions' => fn ($sessions) => $sessions->where('status', 'scheduled')->where('starts_at', '>=', now())->orderBy('starts_at')->limit(1)])
            ->withCount(['students', 'sessions'])
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'course_id', 'teacher_id', 'schedule', 'skyroom_status']);
        $students = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->orderBy('name')
            ->limit(300)
            ->get(['id', 'name']);
        $resources = LearningResource::query()
            ->where('course_id', $course->id)
            ->with('classroom:id,name')
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.courses.form', compact('course', 'teachers', 'videos', 'classrooms', 'grades', 'students', 'resources') + ['isEditing' => true]);
    }

    public function index(
        \App\Http\Requests\Admin\AdminCourseIndexRequest $request,
        ClassroomWorkspaceService $workspace
    ): View {
        $user = $request->user();
        $filters = $request->validated();

        $coursesQuery = $workspace->coursesQueryFor($user)
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
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters): void {
                $term = trim($filters['q']);
                $query->where(function ($search) use ($term): void {
                    $search->where('title', 'like', "%{$term}%")
                        ->orWhere('subject', 'like', "%{$term}%")
                        ->orWhere('grade', 'like', "%{$term}%")
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$term}%"));
                });
            })
            ->when(filled($filters['grade'] ?? null), fn ($query) => $query->where('grade', $filters['grade']))
            ->when(filled($filters['season'] ?? null), fn ($query) => $query->where('season', $filters['season']))
            ->when(filled($filters['academic_year'] ?? null), fn ($query) => $query->where('academic_year', $filters['academic_year']))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']));

        $courses = $coursesQuery->latest()->paginate(9)->withQueryString();
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
        $academicYears = $workspace->coursesQueryFor($user)->whereNotNull('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year');
        $canManage = $user->hasRole('admin') && $user->hasPermissionTo('courses.manage');
        $canManageContent = $user->hasAnyPermission(['courses.manage', 'courses.content.manage.assigned']);

        return view('admin.courses.index', compact('courses', 'stats', 'canManage', 'canManageContent', 'grades', 'filters', 'academicYears'));
    }

    public function store(StoreCourseRequest $request, CourseService $courses): RedirectResponse
    {
        $data = $request->validated();
        $coverPath = $request->hasFile('cover_image')
            ? $request->file('cover_image')->store('course-covers', 'public')
            : null;
        if ($request->hasFile('cover_image') && ! $coverPath) {
            throw ValidationException::withMessages(['cover_image' => 'ذخیره تصویر دوره ناموفق بود؛ دوباره تلاش کنید.']);
        }
        if ($coverPath) {
            $data['cover_image_path'] = $coverPath;
        }
        unset($data['cover_image'], $data['remove_cover_image']);

        try {
            $course = $courses->create($data);
        } catch (Throwable $exception) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }
            throw $exception;
        }

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

        $data = $request->validated();
        $oldCoverPath = $course->cover_image_path;
        $newCoverPath = null;

        if ($request->hasFile('cover_image')) {
            $newCoverPath = $request->file('cover_image')->store('course-covers', 'public');
            if (! $newCoverPath) {
                throw ValidationException::withMessages(['cover_image' => 'ذخیره تصویر دوره ناموفق بود؛ دوباره تلاش کنید.']);
            }
            $data['cover_image_path'] = $newCoverPath;
        } elseif ($request->boolean('remove_cover_image')) {
            $data['cover_image_path'] = null;
        }
        unset($data['cover_image'], $data['remove_cover_image']);

        try {
            $course = $courses->update($course, $data);
        } catch (Throwable $exception) {
            if ($newCoverPath) {
                Storage::disk('public')->delete($newCoverPath);
            }
            throw $exception;
        }

        if ($oldCoverPath && $oldCoverPath !== $course->cover_image_path) {
            Storage::disk('public')->delete($oldCoverPath);
        }

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
