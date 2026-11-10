<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $teacherView = auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin');
        $courses = Course::with('teacher')
            ->withCount(['classrooms', 'enrollments'])
            ->when($teacherView, fn ($query) => $query->where('teacher_id', auth()->id()))
            ->latest()->paginate(12);

        $teachers = User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.courses.index', compact('courses', 'teachers'));
    }

    public function store(StoreCourseRequest $request, CourseService $courses): RedirectResponse
    {
        $course = $courses->create($request->validated());
        $message = $course->delivery_type === 'spotplayer'
            ? 'دوره در شیخان ایجاد شد؛ اتصال به SpotPlayer در انتظار قرارداد وب‌سرویس است.'
            : 'دوره با موفقیت ایجاد شد.';

        return back()->with('success', $message);
    }

    public function update(UpdateCourseRequest $request, Course $course, CourseService $courses): RedirectResponse
    {
        abort_unless(auth()->user()->hasRole('admin') || $course->teacher_id === auth()->id(), 403);

        $course = $courses->update($course, $request->validated());
        $message = $course->delivery_type === 'spotplayer'
            ? 'اطلاعات دوره ذخیره شد؛ شناسه خارجی تا زمان تأیید API معتبر تلقی نمی‌شود.'
            : 'دوره به‌روزرسانی شد.';

        return back()->with('success', $message);
    }

    public function destroy(Course $course, CourseService $courses): RedirectResponse
    {
        $courses->delete($course);

        return back()->with('success', 'دوره حذف شد.');
    }
}
