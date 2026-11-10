<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClassroomRequest;
use App\Http\Requests\Admin\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                        ->whereIn('course_id', $courseIds)
                        ->where('status', 'active'));
            }))
            ->orderBy('name')->get(['id', 'name']);

        return view('admin.classes.index', compact('classrooms', 'courses', 'teachers', 'students', 'teacherView'));
    }

    public function store(
        StoreClassroomRequest $request,
        CourseEnrollmentService $enrollments,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $data = $request->validated();
        $user = $request->user();
        $teacherId = $workspace->isTeacher($user) ? $user->id : (int) $data['teacher_id'];

        DB::transaction(function () use ($data, $enrollments, $teacherId, $user): void {
            $classroom = Classroom::create([
                'name' => $data['name'],
                'course_id' => $data['course_id'] ?? null,
                'teacher_id' => $teacherId,
                'meeting_url' => $data['meeting_url'] ?? null,
                'status' => $data['status'],
                'schedule' => [
                    'days' => $data['schedule_days'] ?? [],
                    'time' => $data['schedule_time'] ?? null,
                    'duration_minutes' => (int) ($data['duration_minutes'] ?? 90),
                ],
                'created_by' => $user->id,
            ]);

            $classroom->students()->syncWithPivotValues(
                $data['student_ids'] ?? [],
                ['assigned_by' => $user->id]
            );
            $enrollments->syncClassroomRoster($classroom);
        });

        return back()->with('success', 'کلاس و لینک ورود آنلاین آن با موفقیت ثبت شد.');
    }

    public function update(
        UpdateClassroomRequest $request,
        Classroom $classroom,
        CourseEnrollmentService $enrollments,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $user = $request->user();
        $classroom = $workspace->queryFor($user)->findOrFail($classroom->id);
        $previousCourseId = $classroom->course_id;
        $data = $request->validated();
        $teacherId = $workspace->isTeacher($user) ? $user->id : (int) $data['teacher_id'];

        DB::transaction(function () use ($classroom, $data, $previousCourseId, $enrollments, $teacherId, $user): void {
            $classroom->update([
                'name' => $data['name'],
                'course_id' => $data['course_id'] ?? null,
                'teacher_id' => $teacherId,
                'meeting_url' => $data['meeting_url'] ?? null,
                'status' => $data['status'],
                'schedule' => [
                    'days' => $data['schedule_days'] ?? [],
                    'time' => $data['schedule_time'] ?? null,
                    'duration_minutes' => (int) ($data['duration_minutes'] ?? 90),
                ],
            ]);

            $classroom->students()->syncWithPivotValues(
                $data['student_ids'] ?? [],
                ['assigned_by' => $user->id]
            );

            if ($previousCourseId && $previousCourseId !== $classroom->course_id) {
                $previousCourse = Course::find($previousCourseId);
                if ($previousCourse) {
                    $enrollments->syncCourseRoster($previousCourse);
                }
            }

            $enrollments->syncClassroomRoster($classroom->fresh());
        });

        return back()->with('success', 'کلاس، زمان‌بندی و لینک اسکای‌روم به‌روزرسانی شد.');
    }

    public function destroy(Classroom $classroom, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $classroom = $workspace->queryFor(auth()->user())->findOrFail($classroom->id);

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
