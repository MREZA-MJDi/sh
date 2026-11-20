<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Notifications\CourseVideoSubmittedForReview;
use Illuminate\Support\Facades\Notification;
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
        Notification::fake();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
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
            'approval_status' => 'pending',
        ]);
        Notification::assertSentTo($admin, CourseVideoSubmittedForReview::class);

        // A teacher may submit a recording for the whole course without choosing a classroom or session.
        $this->actingAs($teacher)
            ->post(route('teacher.spotplayer.videos.store'), [
                'title' => 'ویدیوی مشترک کل دوره',
                'course_id' => $ownCourse->id,
                'external_id' => 'spotplayer-course-wide-456',
                'status' => 'waiting_contract',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('spot_player_videos', [
            'course_id' => $ownCourse->id,
            'title' => 'ویدیوی مشترک کل دوره',
            'classroom_id' => null,
            'class_session_id' => null,
            'approval_status' => 'pending',
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

    public function test_admin_can_approve_or_reject_teacher_submissions_with_a_reason(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $teacher = User::factory()->create();
        $course = $this->courseFor($teacher, 'video-review-course');

        $approved = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'ویدیوی قابل تأیید',
            'status' => 'active',
            'approval_status' => 'pending',
        ]);
        $rejected = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'created_by' => $teacher->id,
            'title' => 'ویدیوی نیازمند اصلاح',
            'status' => 'active',
            'approval_status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.spotplayer.videos.approve', $approved))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('spot_player_videos', [
            'id' => $approved->id,
            'approval_status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.spotplayer.index'))
            ->post(route('admin.spotplayer.videos.reject', $rejected), ['reason' => 'صدای ویدیو واضح نیست'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('spot_player_videos', [
            'id' => $rejected->id,
            'approval_status' => 'rejected',
            'review_note' => 'صدای ویدیو واضح نیست',
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
