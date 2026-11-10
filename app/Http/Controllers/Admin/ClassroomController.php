<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassroomRequest;
use App\Http\Requests\Admin\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Http\RedirectResponse;
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

    public function store(
        StoreClassroomRequest $request,
        CourseEnrollmentService $enrollments
    ): RedirectResponse {
        $data = $request->validated();

        $classroom = Classroom::create([
            'name' => $data['name'],
            'course_id' => $data['course_id'] ?? null,
            'teacher_id' => $data['teacher_id'],
            'meeting_url' => $data['meeting_url'] ?? null,
            'status' => $data['status'],
            'schedule' => [
                'days' => $data['schedule_days'] ?? [],
                'time' => $data['schedule_time'] ?? null,
            ],
            'created_by' => auth()->id(),
        ]);

        $classroom->students()->syncWithPivotValues(
            $data['student_ids'] ?? [],
            ['assigned_by' => auth()->id()]
        );

        $enrollments->syncClassroomRoster($classroom);

        return back()->with('success', 'کلاس ساخته شد؛ دسترسی دوره رایگان برای اعضای آن ثبت شد.');
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->update($request->validated());

        return back()->with('success', 'کلاس به‌روزرسانی شد.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        abort_if(
            $classroom->homeworks()->exists() ||
            $classroom->attendances()->exists() ||
            $classroom->students()->exists(),
            422,
            'کلاس دارای سوابق آموزشی یا اعضای ثبت‌نام‌شده است؛ آن را بایگانی کنید.'
        );

        $classroom->delete();

        return back()->with('success', 'کلاس حذف شد.');
    }
}
