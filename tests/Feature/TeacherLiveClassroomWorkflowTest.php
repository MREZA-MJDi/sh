<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherLiveClassroomWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_sees_only_assigned_online_sessions_and_can_update_a_secure_join_url(): void
    {
        $teacher = User::factory()->create(['permissions_customized' => true]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $teacher->permissions()->sync(Permission::query()->whereIn('name', [
            'dashboard.view', 'classes.view.assigned', 'classes.manage.assigned',
        ])->pluck('id'));

        $ownCourse = $this->courseFor($teacher, 'teacher-live-own-course');
        $otherTeacher = User::factory()->create();
        $otherCourse = $this->courseFor($otherTeacher, 'teacher-live-other-course');

        $ownClassroom = Classroom::query()->create([
            'course_id' => $ownCourse->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس آنلاین من',
            'status' => 'active',
        ]);
        $otherClassroom = Classroom::query()->create([
            'course_id' => $otherCourse->id,
            'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس مدرس دیگر',
            'status' => 'active',
        ]);

        $ownSession = $this->sessionFor($ownClassroom, 'جلسه مجاز');
        $otherSession = $this->sessionFor($otherClassroom, 'جلسه محرمانه');

        $this->actingAs($teacher)
            ->get(route('teacher.live'))
            ->assertOk()
            ->assertSee('جلسه مجاز')
            ->assertDontSee('جلسه محرمانه')
            ->assertSee('فهرست جلسه‌ها');

        $this->actingAs($teacher)
            ->patch(route('teacher.live.update', $ownSession), [
                'meeting_provider' => 'jitsi',
                'meeting_url' => 'https://meet.example.test/teacher-room',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_sessions', [
            'id' => $ownSession->id,
            'meeting_provider' => 'jitsi',
            'meeting_url' => 'https://meet.example.test/teacher-room',
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.live'))
            ->patch(route('teacher.live.update', $ownSession), [
                'meeting_provider' => 'jitsi',
                'meeting_url' => 'http://insecure.example.test/room',
            ])
            ->assertSessionHasErrors('meeting_url');

        $this->actingAs($teacher)
            ->patch(route('teacher.live.update', $otherSession), [
                'meeting_provider' => 'jitsi',
                'meeting_url' => 'https://meet.example.test/not-yours',
            ])
            ->assertNotFound();
    }

    private function courseFor(User $teacher, string $slug): Course
    {
        return Course::query()->create([
            'title' => 'دوره '.$slug,
            'slug' => $slug,
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
    }

    private function sessionFor(Classroom $classroom, string $title): ClassSession
    {
        return ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => $title,
            'session_type' => 'online',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'meeting_provider' => 'jitsi',
            'status' => 'scheduled',
        ]);
    }
}
