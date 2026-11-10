<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    private const MANAGED_ROLES = ['student', 'teacher', 'parent'];

    public function index(Request $request, string $role): View
    {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);

        $users = User::query()
            ->with('roles')
            ->whereHas('roles', fn ($roles) => $roles->where('name', $role))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->string('q')->toString());
                $query->where(fn ($search) => $search
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->latest()->paginate(15)->withQueryString();

        $students = User::whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->orderBy('name')->get(['id', 'name']);
        $classrooms = Classroom::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'role', 'students', 'classrooms'));
    }

    public function store(
        StoreUserRequest $request,
        string $role,
        CourseEnrollmentService $enrollments
    ): RedirectResponse {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $role) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'grade' => $role === 'student' ? ($data['grade'] ?? null) : null,
                'subject' => $role === 'teacher' ? ($data['subject'] ?? null) : null,
                'is_active' => true,
            ]);

            $user->roles()->attach(Role::where('name', $role)->firstOrFail()->id);

            if ($role === 'student' && ! empty($data['classroom_ids'])) {
                $user->enrolledClasses()->syncWithPivotValues(
                    $data['classroom_ids'],
                    ['assigned_by' => auth()->id()]
                );
            }

            if ($role === 'parent' && ! empty($data['child_ids'])) {
                $user->children()->sync($data['child_ids']);
            }

            return $user;
        });

        if ($role === 'student') {
            $user->load('enrolledClasses.course');

            foreach ($user->enrolledClasses as $classroom) {
                $enrollments->syncClassroomRoster($classroom);
            }
        }

        return back()->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('admin'), 403, 'وضعیت مدیر سامانه از این بخش قابل تغییر نیست.');

        $user->update($request->validated());

        return back()->with('success', 'وضعیت حساب کاربری به‌روزرسانی شد.');
    }
}
