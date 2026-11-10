<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassroomRequest;
use App\Http\Requests\Admin\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\Admin\ClassroomService;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $user = $request->user();
        $teacherView = $workspace->isTeacher($user);

        $classrooms = $workspace->queryFor($user)
            ->with(['course', 'teacher', 'students:id,name'])
            ->withCount('students')->latest()->paginate(15);

        $courses = $workspace->coursesQueryFor($user)
            ->whereIn('status', ['published', 'draft'])
            ->orderBy('title')->get(['id', 'title', 'teacher_id']);

        $teachers = $teacherView
            ? collect([$user])
            : User::query()->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
                ->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $classroomIds = $workspace->queryFor($user)->select('classrooms.id');
        $courseIds = $workspace->coursesQueryFor($user)->select('courses.id');

        $students = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->where('is_active', true)
            ->when($teacherView, fn ($query) => $query->where(function ($scope) use ($classroomIds, $courseIds): void {
                $scope->whereHas('enrolledClasses', fn ($classes) => $classes->whereIn('classrooms.id', $classroomIds))
                    ->orWhereHas('courseEnrollments', fn ($enrollments) => $enrollments
                        ->whereIn('course_id', $courseIds)->where('status', 'active'));
            }))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.classes.index', compact('classrooms', 'courses', 'teachers', 'students', 'teacherView'));
    }

    public function store(StoreClassroomRequest $request, ClassroomService $classrooms): RedirectResponse
    {
        $classrooms->create($request->user(), $request->validated());

        return back()->with('success', 'کلاس و لینک ورود آنلاین آن با موفقیت ثبت شد.');
    }

    public function update(
        UpdateClassroomRequest $request,
        Classroom $classroom,
        ClassroomService $classrooms
    ): RedirectResponse {
        $classrooms->update($request->user(), $classroom, $request->validated());

        return back()->with('success', 'کلاس، زمان‌بندی و لینک اسکای‌روم به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Classroom $classroom, ClassroomService $classrooms): RedirectResponse
    {
        $classrooms->delete($request->user(), $classroom);

        return back()->with('success', 'کلاس حذف شد.');
    }
}
