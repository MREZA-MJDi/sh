<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherVideoRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_can_stage_videos_only_for_assigned_courses(): void
    {
        $teacher = User::factory()->create(['permissions_customized' => true]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));
        $teacher->permissions()->sync(Permission::query()->whereIn('name', [
            'dashboard.view', 'videos.manage.assigned',
        ])->pluck('id'));

        $ownCourse = $this->courseFor($teacher, 'teacher-video-own-course');
        $classroom = Classroom::query()->create([
            'course_id' => $ownCourse->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس مدرس',
            'status' => 'active',
        ]);
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه اول',
            'session_type' => 'online',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'meeting_provider' => 'jitsi',
            'status' => 'scheduled',
        ]);
        $otherTeacher = User::factory()->create();
        $otherCourse = $this->courseFor($otherTeacher, 'teacher-video-other-course');

        $this->actingAs($teacher)
            ->get(route('teacher.videos'))
            ->assertOk()
            ->assertSee($ownCourse->title)
            ->assertDontSee($otherCourse->title);

        $this->actingAs($teacher)
            ->post(route('teacher.spotplayer.videos.store'), [
                'title' => 'ویدئوی دوره من',
                'course_id' => $ownCourse->id,
                'classroom_id' => $classroom->id,
                'class_session_id' => $session->id,
                'external_id' => 'spotplayer-video-123',
                'status' => 'waiting_contract',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('spot_player_videos', [
            'course_id' => $ownCourse->id,
            'title' => 'ویدئوی دوره من',
            'external_id' => 'spotplayer-video-123',
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($teacher)
            ->from(route('teacher.videos'))
            ->post(route('teacher.spotplayer.videos.store'), [
                'title' => 'ویدئوی دوره غیرمجاز',
                'course_id' => $otherCourse->id,
                'external_id' => 'forbidden-video',
                'status' => 'waiting_contract',
            ])
            ->assertSessionHasErrors('course_id');

        $this->assertDatabaseMissing('spot_player_videos', [
            'title' => 'ویدئوی دوره غیرمجاز',
        ]);
    }

    private function courseFor(User $teacher, string $slug): Course
    {
        return Course::query()->create([
            'title' => 'دوره '.$slug,
            'slug' => $slug,
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'status' => 'published',
        ]);
    }
}
