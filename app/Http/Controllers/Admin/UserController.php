<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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

    public function store(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'grade' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:100'],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $classroomIds = [];
        $childIds = [];

        if ($role === 'student' && ! empty($data['classroom_ids'])) {
            $classroomIds = Classroom::whereIn('id', $data['classroom_ids'])
                ->where('status', 'active')->pluck('id')->all();

            if (count($classroomIds) !== count($data['classroom_ids'])) {
                throw ValidationException::withMessages([
                    'classroom_ids' => 'همه کلاس‌های انتخاب‌شده فعال و معتبر نیستند.',
                ]);
            }
        }

        if ($role === 'parent' && ! empty($data['child_ids'])) {
            $childIds = User::whereIn('id', $data['child_ids'])
                ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                ->pluck('id')->all();

            if (count($childIds) !== count($data['child_ids'])) {
                throw ValidationException::withMessages([
                    'child_ids' => 'فقط حساب‌های دارای نقش دانش‌آموز قابل اتصال هستند.',
                ]);
            }
        }

        DB::transaction(function () use ($data, $role, $classroomIds, $childIds) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'grade' => $role === 'student' ? ($data['grade'] ?? null) : null,
                'subject' => $role === 'teacher' ? ($data['subject'] ?? null) : null,
                'is_active' => true,
            ]);

            $roleId = Role::where('name', $role)->value('id');
            abort_unless($roleId, 500, 'نقش کاربری تعریف نشده است.');

            $user->roles()->attach($roleId);

            if ($role === 'student' && $classroomIds !== []) {
                $user->enrolledClasses()->syncWithPivotValues($classroomIds, ['assigned_by' => auth()->id()]);
            }

            if ($role === 'parent' && $childIds !== []) {
                $user->children()->sync($childIds);
            }
        });

        return back()->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('admin'), 403, 'وضعیت مدیر سامانه از این بخش قابل تغییر نیست.');

        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update(['is_active' => $data['is_active']]);

        return back()->with('success', 'وضعیت حساب کاربری به‌روزرسانی شد.');
    }
}
