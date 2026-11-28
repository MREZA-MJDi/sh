<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AuthenticationService
{
    public function registerStudent(array $data): User
    {
        return $this->registerAccount($data, 'student');
    }

    public function registerParent(array $data): User
    {
        return $this->registerAccount($data, 'parent');
    }

    private function registerAccount(array $data, string $roleName): User
    {
        return DB::transaction(function () use ($data, $roleName): User {
            $role = Role::query()->where('name', $roleName)->firstOrFail();

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'is_active' => true,
            ]);

            $user->roles()->sync([$role->getKey()]);

            return $user;
        });
    }

    public function dashboardRouteName(User $user): ?string
    {
        return match (true) {
            $user->hasRole('admin') => 'admin.dashboard',
            $user->hasRole('seo') => 'admin.seo.index',
            $user->hasRole('teacher') => 'teacher.dashboard',
            $user->hasRole('student') => 'student.dashboard',
            $user->hasRole('parent') => 'guardian.dashboard',
            default => null,
        };
    }
}
