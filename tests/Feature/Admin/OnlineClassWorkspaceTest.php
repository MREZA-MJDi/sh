<?php

namespace Tests\Feature\Admin;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OnlineClassWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_only_sees_online_sessions_for_their_assigned_classrooms(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $otherTeacher = User::factory()->create();
        $otherTeacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $ownClassroom = Classroom::query()->create([
            'name' => 'کلاس خود مدرس',
            'teacher_id' => $teacher->id,
            'status' => 'active',
        ]);
        $otherClassroom = Classroom::query()->create([
            'name' => 'کلاس مدرس دیگر',
            'teacher_id' => $otherTeacher->id,
            'status' => 'active',
        ]);

        ClassSession::query()->create([
            'classroom_id' => $ownClassroom->id,
            'title' => 'جلسه مجاز',
            'session_type' => 'online',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'created_by' => $teacher->id,
        ]);
        ClassSession::query()->create([
            'classroom_id' => $otherClassroom->id,
            'title' => 'جلسه غیرمجاز',
            'session_type' => 'online',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
            'created_by' => $otherTeacher->id,
        ]);

        $response = $this->actingAs($teacher)->get(route('admin.online-classes.index'));

        $response->assertOk()
            ->assertSee('جلسه مجاز')
            ->assertDontSee('جلسه غیرمجاز')
            ->assertViewHas('stats.upcoming', 1)
            ->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 1);
    }
}
