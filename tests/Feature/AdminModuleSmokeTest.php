<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_directory_counts_courses_without_eager_loading_every_course(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $teacher = User::factory()->create(['name' => 'مدرس فهرست']);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        foreach (range(1, 8) as $index) {
            Course::query()->create([
                'title' => 'دوره مدرس '.$index,
                'slug' => 'teacher-directory-'.$index.'-'.uniqid(),
                'teacher_id' => $teacher->id,
                'type' => 'free',
                'price' => 0,
                'delivery_type' => 'onsite',
                'status' => 'published',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.teachers.index'))
            ->assertOk()
            ->assertSee('8 دوره')
            ->assertViewHas('users', function ($users) use ($teacher): bool {
                $listedTeacher = $users->getCollection()->firstWhere('id', $teacher->id);

                return $listedTeacher !== null
                    && (int) $listedTeacher->assigned_courses_count === 8
                    && ! $listedTeacher->relationLoaded('assignedCourses');
            });
    }

    public function test_all_client_facing_admin_workspaces_render_for_an_admin_account(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $routes = [
            'admin.dashboard',
            'admin.contact-messages.index',
            'admin.students.index',
            'admin.teachers.index',
            'admin.parents.index',
            'admin.enrollments.index',
            'admin.payments.index',
            'admin.online-classes.index',
            'admin.courses.index',
            'admin.classes.index',
            'admin.parent-meetings.index',
            'admin.attendance.index',
            'admin.homework.index',
            'admin.exams.index',
            'admin.resources.index',
            'admin.consultations.index',
            'admin.messages.index',
            'admin.announcements.index',
            'admin.spotplayer.index',
            'admin.quiz24.index',
        ];

        foreach ($routes as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk();
        }
    }
}
