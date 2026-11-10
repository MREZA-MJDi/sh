<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AuthenticationService
{
    public function registerStudent(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $studentRole = Role::query()->where('name', 'student')->firstOrFail();

            $student = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $student->roles()->sync([$studentRole->getKey()]);

            return $student;
        });
    }

    public function dashboardRouteName(User $user): ?string
    {
        return match (true) {
            $user->hasRole('admin') => 'admin.dashboard',
            $user->hasRole('teacher') => 'teacher.dashboard',
            $user->hasRole('student') => 'student.dashboard',
            $user->hasRole('parent') => 'guardian.dashboard',
            default => null,
        };
    }
}
