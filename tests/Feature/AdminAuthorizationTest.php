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

    public function test_guest_is_redirected_to_the_shared_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
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

    public function test_admin_workspace_uses_rtl_reference_layout_and_grouped_sidebar(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<html lang="fa" dir="rtl">', false)
            ->assertSee('data-panel-shell', false)
            ->assertSee('data-panel-sidebar-mini-toggle', false)
            ->assertSee('data-nav-group-toggle', false)
            ->assertSee('فروش و محصولات')
            ->assertSee('میانبرهای سریع');
    }

    public function test_every_admin_section_renders_without_sample_records(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->value('id'));

        $pages = [
            route('admin.dashboard'),
            route('admin.classes.index'),
            route('admin.courses.index'),
            route('admin.students.index'),
            route('admin.teachers.index'),
            route('admin.parents.index'),
            route('admin.attendance.index'),
            route('admin.homework.index'),
            route('admin.exams.index'),
            route('admin.resources.index'),
            route('admin.consultations.index'),
            route('admin.messages.index'),
            route('admin.announcements.index'),
            route('admin.spotplayer.index'),
            route('admin.quiz24.index'),
        ];

        $this->actingAs($admin);

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
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
