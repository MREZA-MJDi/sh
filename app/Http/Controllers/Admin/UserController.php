<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateTeacherAccessRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\TeacherAccessService;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    private const MANAGED_ROLES = ['student', 'teacher', 'parent'];

    public function index(
        Request $request,
        string $role,
        TeacherAccessService $teacherAccess,
        ClassroomWorkspaceService $workspace
    ): View {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);

        $teacherView = $workspace->isTeacher($request->user());
        abort_if($teacherView && $role !== 'student', 403);

        $classroomIds = $workspace->queryFor($request->user())->select('classrooms.id');
        $courseIds = $workspace->coursesQueryFor($request->user())->select('courses.id');

        $users = User::query()
            ->with(['roles.permissions', 'permissions', 'assignedCourses'])
            ->whereHas('roles', fn ($roles) => $roles->where('name', $role))
            ->when($teacherView, fn ($query) => $query->where(function ($scope) use ($classroomIds, $courseIds): void {
                $scope->whereHas('enrolledClasses', fn ($classes) => $classes->whereIn('classrooms.id', $classroomIds))
                    ->orWhereHas('courseEnrollments', fn ($enrollments) => $enrollments
                        ->whereIn('course_id', $courseIds)
                        ->where('status', 'active'));
            }))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = trim($request->string('q')->toString());
                $query->where(fn ($search) => $search
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->latest()->paginate(15)->withQueryString();

        $students = collect();
        $classrooms = collect();

        // Only prepare the selector data required by the active user form.
        // This avoids loading the full student roster on teacher pages or the
        // classroom list on parent/teacher pages.
        if ($role === 'parent') {
            $students = User::whereHas('roles', fn ($query) => $query->where('name', 'student'))
                ->orderBy('name')->get(['id', 'name']);
        }

        if ($role === 'student') {
            $classrooms = $workspace->queryFor($request->user())->where('status', 'active')
                ->orderBy('name')->get(['id', 'name']);
        }
        $courses = $role === 'teacher'
            ? Course::orderBy('title')->get(['id', 'title', 'teacher_id'])
            : collect();

        $teacherAccessGroups = TeacherAccessService::ACCESS_GROUPS;
        $teacherSelectedGroups = $role === 'teacher'
            ? $users->getCollection()->mapWithKeys(fn (User $teacher) => [
                $teacher->id => $teacherAccess->selectedGroups($teacher),
            ])
            : collect();

        return view('admin.users.index', compact(
            'users', 'role', 'students', 'classrooms', 'courses',
            'teacherAccessGroups', 'teacherSelectedGroups'
        ));
    }

    public function store(
        StoreUserRequest $request,
        string $role,
        UserManagementService $users
    ): RedirectResponse {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);
        $users->create($request->validated(), $role, $request->user());

        return back()->with('success', $role === 'teacher'
            ? 'مدرس ساخته شد و دوره‌ها و دسترسی‌های انتخاب‌شده برای او ثبت شد. برای این حساب گذرواژه اولیه تعیین شده است؛ پیامک خودکار در سامانه فعال نیست.'
            : 'کاربر با موفقیت ایجاد شد.');
    }

    public function updateTeacher(
        UpdateTeacherAccessRequest $request,
        User $teacher,
        UserManagementService $users
    ): RedirectResponse {
        $users->updateTeacher($teacher, $request->validated(), $request->user());

        return back()->with('success', 'اطلاعات، دوره‌ها و دسترسی‌های مدرس به‌روزرسانی شد.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('admin'), 403, 'وضعیت مدیر سامانه از این بخش قابل تغییر نیست.');

        $user->update($request->validated());

        return back()->with('success', 'وضعیت حساب کاربری به‌روزرسانی شد.');
    }
}
