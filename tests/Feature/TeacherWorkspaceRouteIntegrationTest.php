<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherWorkspaceRouteIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_dashboard_and_workspace_shortcuts_resolve_to_existing_scoped_pages(): void
    {
        $teacher = User::factory()->create(['permissions_customized' => true]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $teacher->permissions()->sync(Permission::query()
            ->whereIn('name', [
                'dashboard.view',
                'courses.view.assigned',
                'classes.view.assigned',
                'classes.manage.assigned',
                'homework.view.assigned',
                'homework.manage.assigned',
                'homework.grade',
                'videos.manage.assigned',
                'resources.manage.assigned',
                'consultations.create',
                'messages.view.assigned',
            ])
            ->pluck('id'));

        Course::query()->create([
            'title' => 'دوره مدرس برای آزمون',
            'slug' => 'teacher-workspace-route-integration',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('برنامه هفت روز آینده')
            ->assertSee(route('teacher.videos'), false)
            ->assertSee(route('teacher.resources'), false);

        $this->actingAs($teacher)
            ->get(route('teacher.videos'))
            ->assertOk()
            ->assertSee('بارگذاری فیلم‌ها');

        $this->actingAs($teacher)
            ->get(route('teacher.resources'))
            ->assertOk()
            ->assertSee('بارگذاری جزوه و منابع');

        $this->actingAs($teacher)
            ->get(route('teacher.homework'))
            ->assertOk()
            ->assertSee('تکالیف کلاس‌ها');

        $this->actingAs($teacher)->get(route('admin.homework.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.resources.index'))->assertForbidden();
        $this->actingAs($teacher)->get(route('admin.spotplayer.index'))->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('teacher.consultations.index'))
            ->assertOk()
            ->assertSee('زمان پیشنهادی')
            ->assertDontSee('])addMinute');
    }
}
