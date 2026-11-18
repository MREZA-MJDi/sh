<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateTeacherAccessRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Http\Requests\Admin\UpdateParentRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\Classroom;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    private const MANAGED_ROLES = ['student', 'teacher', 'parent'];

    public function index(Request $request, string $role, ClassroomWorkspaceService $workspace): View
    {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);

        $teacherView = $workspace->isTeacher($request->user());
        abort_if($teacherView && $role !== 'student', 403);

        $classroomIds = $workspace->queryFor($request->user())->select('classrooms.id');
        $courseIds = $workspace->coursesQueryFor($request->user())->select('courses.id');

        $users = User::query()
            ->when($role === 'student', fn ($query) => $query->with('enrolledClasses:id,name'))
            ->when($role === 'teacher', fn ($query) => $query->with('assignedCourses:id,title,teacher_id'))
            ->when($role === 'parent', fn ($query) => $query->with('children:id,name'))
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

        $classrooms = collect();
        $selectedChildren = collect();

        if ($role === 'parent') {
            $previousChildIds = array_slice((array) session()->get('_old_input.child_ids', []), 0, 30);

            if ($previousChildIds !== []) {
                $selectedChildren = User::query()
                    ->whereIn('id', $previousChildIds)
                    ->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        }

        if ($role === 'student') {
            $classrooms = $workspace->queryFor($request->user())->where('status', 'active')
                ->orderBy('name')->get(['id', 'name']);
        }

        return view('admin.users.index', compact('users', 'role', 'classrooms', 'selectedChildren'));
    }

    public function store(StoreUserRequest $request, string $role, UserManagementService $users): RedirectResponse
    {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);
        $users->create($request->validated(), $role, $request->user());

        return back()->with('success', $role === 'teacher'
            ? 'حساب مدرس ایجاد شد؛ دسترسی‌های پایه از نقش مدرس دریافت می‌شود و تخصیص دوره از بخش مدیریت دوره‌ها انجام می‌شود.'
            : 'کاربر با موفقیت ایجاد شد.');
    }

    public function updateTeacher(
        UpdateTeacherAccessRequest $request,
        User $teacher,
        UserManagementService $users
    ): RedirectResponse {
        $users->updateTeacher($teacher, $request->validated(), $request->user());

        return back()->with('success', 'اطلاعات حساب مدرس به‌روزرسانی شد.');
    }

    public function updateStudent(
        UpdateStudentRequest $request,
        User $student,
        UserManagementService $users
    ): RedirectResponse {
        $users->updateStudent($student, $request->validated(), $request->user());

        return back()->with('success', 'اطلاعات دانش‌آموز و عضویت کلاس‌ها به‌روزرسانی شد.');
    }

    public function updateParent(
        UpdateParentRequest $request,
        User $parent,
        UserManagementService $users
    ): RedirectResponse {
        $users->updateParent($parent, $request->validated());

        return back()->with('success', 'اطلاعات ولی و ارتباط دانش‌آموزان به‌روزرسانی شد.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('admin') || ($user->hasRole('seo') && ! $request->user()->hasRole('seo')), 403, 'وضعیت این حساب کاربری از این بخش قابل تغییر نیست.');

        $user->update($request->validated());

        return back()->with('success', 'وضعیت حساب کاربری به‌روزرسانی شد.');
    }
}
