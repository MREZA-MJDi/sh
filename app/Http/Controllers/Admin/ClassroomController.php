<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(): View
    {
        $classrooms = Classroom::with(['course', 'teacher'])
            ->withCount('students')->latest()->paginate(15);
        $courses = Course::whereIn('status', ['published', 'draft'])->orderBy('title')->get(['id', 'title']);
        $teachers = User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $students = User::whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.classes.index', compact('classrooms', 'courses', 'teachers', 'students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => [Rule::in(['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'])],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        abort_unless(User::findOrFail($data['teacher_id'])->hasRole('teacher'), 422, 'کاربر انتخاب‌شده معلم نیست.');

        $selectedStudentIds = $data['student_ids'] ?? [];
        $studentIds = User::whereIn('id', $selectedStudentIds)
            ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->pluck('id')->all();

        abort_if(count($studentIds) !== count($selectedStudentIds), 422, 'فقط حساب‌های دارای نقش دانش‌آموز قابل افزودن به کلاس هستند.');

        $classroom = Classroom::create([
            'name' => $data['name'],
            'course_id' => $data['course_id'] ?? null,
            'teacher_id' => $data['teacher_id'],
            'meeting_url' => $data['meeting_url'] ?? null,
            'status' => $data['status'],
            'schedule' => ['days' => $data['schedule_days'] ?? [], 'time' => $data['schedule_time'] ?? null],
            'created_by' => auth()->id(),
        ]);

        $classroom->students()->syncWithPivotValues($studentIds, ['assigned_by' => auth()->id()]);

        return back()->with('success', 'کلاس با موفقیت ایجاد شد.');
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
        ]);

        abort_unless(User::findOrFail($data['teacher_id'])->hasRole('teacher'), 422, 'کاربر انتخاب‌شده معلم نیست.');

        $classroom->update($data);

        return back()->with('success', 'کلاس به‌روزرسانی شد.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        abort_if(
            $classroom->homeworks()->exists() || $classroom->attendances()->exists() || $classroom->students()->exists(),
            422,
            'کلاس دارای سوابق آموزشی یا اعضای ثبت‌نام‌شده است؛ آن را بایگانی کنید.'
        );

        $classroom->delete();

        return back()->with('success', 'کلاس حذف شد.');
    }
}
