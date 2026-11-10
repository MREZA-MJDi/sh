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
use Illuminate\View\View;

class UserController extends Controller
{
    private const MANAGED_ROLES = ['student', 'teacher', 'parent'];

    public function index(Request $request, string $role): View
    {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);

        $query = User::query()
            ->with('roles')
            ->whereHas('roles', fn ($roles) => $roles->where('name', $role))
            ->when($request->filled('q'), function ($users) use ($request) {
                $term = trim($request->string('q')->toString());

                $users->where(fn ($search) => $search
                    ->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->latest();

        $users = $query->paginate(15)->withQueryString();
        $students = User::whereHas('roles', fn ($q) => $q->where('name', 'student'))->orderBy('name')->get(['id', 'name']);
        $classrooms = Classroom::where('status', 'active')->orderBy('name')->get(['id', 'name']);

        return view('admin.users.index', compact('users', 'role', 'students', 'classrooms'));
    }

    public function store(Request $request, string $role): RedirectResponse
    {
        abort_unless(in_array($role, self::MANAGED_ROLES, true), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'grade' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:100'],
            'classroom_ids' => ['nullable', 'array'],
            'classroom_ids.*' => ['integer', 'exists:classrooms,id'],
            'child_ids' => ['nullable', 'array'],
            'child_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $email = $data['email'] ?: ('user+'.str()->uuid().'@shikhan.local');

        DB::transaction(function () use ($data, $role, $email) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'grade' => $role === 'student' ? ($data['grade'] ?? null) : null,
                'subject' => $role === 'teacher' ? ($data['subject'] ?? null) : null,
                'is_active' => true,
            ]);

            $user->roles()->attach(Role::where('name', $role)->value('id'));

            if ($role === 'student' && ! empty($data['classroom_ids'])) {
                $user->enrolledClasses()->syncWithPivotValues($data['classroom_ids'], ['assigned_by' => auth()->id()]);
            }

            if ($role === 'parent' && ! empty($data['child_ids'])) {
                $validChildren = User::whereIn('id', $data['child_ids'])
                    ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                    ->pluck('id')
                    ->all();

                $user->children()->sync($validChildren);
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
