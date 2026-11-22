<?php

namespace Tests\Feature\Admin;

use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminUserDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_directory_filters_are_applied_server_side_and_counts_match_the_filtered_query(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $activeStudent = User::factory()->create([
            'name' => 'دانش‌آموز فعال پایه دهم',
            'grade' => 'دهم',
            'is_active' => true,
        ]);
        $activeStudent->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $inactiveStudent = User::factory()->create([
            'name' => 'دانش‌آموز غیرفعال پایه یازدهم',
            'grade' => 'یازدهم',
            'is_active' => false,
        ]);
        $inactiveStudent->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $classroom = Classroom::query()->create([
            'name' => 'کلاس پایه دهم',
            'teacher_id' => $admin->id,
            'status' => 'active',
        ]);
        $activeStudent->enrolledClasses()->attach($classroom->id, ['assigned_by' => $admin->id]);

        $response = $this->actingAs($admin)->get(route('admin.students.index', [
            'q' => 'فعال',
            'status' => 'active',
            'grade' => 'دهم',
            'classroom_id' => $classroom->id,
        ]));

        $response->assertOk()
            ->assertSee('دانش‌آموز فعال پایه دهم')
            ->assertDontSee('دانش‌آموز غیرفعال پایه یازدهم')
            ->assertViewHas('users', fn ($users) => $users->total() === 1)
            ->assertViewHas('activeCount', 1)
            ->assertViewHas('inactiveCount', 0)
            ->assertViewHas('filters.classroom_id', (string) $classroom->id);
    }
}
