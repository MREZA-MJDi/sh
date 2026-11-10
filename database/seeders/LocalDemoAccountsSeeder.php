<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

final class LocalDemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || ! config('demo.enabled')) {
            return;
        }

        $password = config('demo.password');

        if (! is_string($password) || mb_strlen($password) < 12) {
            throw new RuntimeException('SHIKHAN_DEMO_PASSWORD must contain at least 12 characters when demo accounts are enabled.');
        }

        $accounts = [
            ['name' => 'مدیر آزمایشی', 'email' => 'admin@sh.local', 'role' => 'admin'],
            ['name' => 'مدرس آزمایشی', 'email' => 'teacher@sh.local', 'role' => 'teacher'],
            ['name' => 'دانش‌آموز آزمایشی', 'email' => 'student@sh.local', 'role' => 'student'],
            ['name' => 'ولی آزمایشی', 'email' => 'parent@sh.local', 'role' => 'parent'],
        ];

        foreach ($accounts as $account) {
            $role = Role::query()->where('name', $account['role'])->firstOrFail();

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $password,
                    'is_active' => true,
                ]
            );

            $user->roles()->sync([$role->getKey()]);
        }
    }
}
