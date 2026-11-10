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

        $users = [];

        foreach (config('demo.accounts', []) as $account) {
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
            $users[$account['role']] = $user;
        }

        // Give the test guardian a real linked student account so that the
        // parent dashboard can exercise its ownership-scoped child queries.
        if (isset($users['parent'], $users['student'])) {
            $users['parent']->children()->sync([$users['student']->getKey()]);
        }
    }
}
