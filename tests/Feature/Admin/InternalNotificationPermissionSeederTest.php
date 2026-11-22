<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\InternalNotificationPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class InternalNotificationPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_standalone_seeder_adds_admin_permission_without_overwriting_existing_role_permissions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        $teacherRole = Role::query()->where('name', 'teacher')->firstOrFail();
        $notificationPermission = Permission::query()->where('name', 'notifications.manage')->firstOrFail();

        $adminRole->permissions()->detach($notificationPermission->id);

        $customPermission = Permission::query()->create([
            'name' => 'custom.audit.view',
            'label' => 'مشاهده گزارش سفارشی',
            'group' => 'custom',
        ]);
        $adminRole->permissions()->attach($customPermission->id);

        $this->seed(InternalNotificationPermissionSeeder::class);

        $adminPermissions = $adminRole->fresh()->permissions->pluck('name');
        $teacherPermissions = $teacherRole->fresh()->permissions->pluck('name');

        $this->assertTrue($adminPermissions->contains('notifications.manage'));
        $this->assertTrue($adminPermissions->contains('custom.audit.view'));
        $this->assertFalse($teacherPermissions->contains('notifications.manage'));
    }
}
