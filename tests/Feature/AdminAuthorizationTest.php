<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('داشبورد مدیریت');
    }

    public function test_teacher_cannot_enter_admin_area(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('name', 'teacher')->value('id'));

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_teacher_has_only_assigned_work_permissions(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::where('name', 'teacher')->value('id'));

        $this->assertTrue($teacher->hasPermissionTo('homework.grade'));
        $this->assertTrue($teacher->hasPermissionTo('attendance.manage.assigned'));
        $this->assertFalse($teacher->hasPermissionTo('users.manage'));
        $this->assertFalse($teacher->hasPermissionTo('integrations.manage'));
    }

    public function test_student_and_parent_permissions_are_separated(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::where('name', 'student')->value('id'));

        $parent = User::factory()->create();
        $parent->roles()->attach(Role::where('name', 'parent')->value('id'));

        $this->assertTrue($student->hasPermissionTo('homework.submit'));
        $this->assertTrue($student->hasPermissionTo('attendance.view.own'));
        $this->assertFalse($student->hasPermissionTo('attendance.view.children'));

        $this->assertTrue($parent->hasPermissionTo('attendance.view.children'));
        $this->assertTrue($parent->hasPermissionTo('students.view.linked'));
        $this->assertFalse($parent->hasPermissionTo('homework.submit'));
    }
}
