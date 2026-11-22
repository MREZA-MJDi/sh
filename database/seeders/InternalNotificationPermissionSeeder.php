<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

final class InternalNotificationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::query()->updateOrCreate(
            ['name' => 'notifications.manage'],
            [
                'label' => 'ارسال و مدیریت اعلان‌های درون‌پنلی',
                'group' => 'notifications',
            ]
        );

        $adminRole = Role::query()->where('name', 'admin')->first();

        $adminRole?->permissions()->syncWithoutDetaching([$permission->id]);
    }
}
