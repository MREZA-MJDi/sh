<?php

namespace Tests\Feature;

use App\Jobs\Learning\ProcessTeaserVideo;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class CourseTeaserUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
        Queue::fake();
    }

    public function test_admin_can_upload_course_teaser_in_chunks_and_queue_processing(): void
    {
        $admin = $this->admin();
        $course = $this->course();

        $start = $this->actingAs($admin)->post(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
        ])->assertOk()->assertJsonPath('ok', true);

        $uploadId = $start->json('upload_id');
        $mp4Header = pack('N', 24).'ftypmp42'.pack('N', 0).'mp42isom';
        $this->post(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('part.mp4', $mp4Header),
        ])->assertOk();

        $this->post(route('admin.uploads.complete'), [
            'upload_id' => $uploadId,
            'total' => 1,
            'filename' => 'course-teaser.mp4',
        ])->assertOk()->assertJsonPath('status', 'processing');

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'teaser_status' => 'processing']);
        $this->assertDatabaseHas('video_upload_sessions', ['upload_id' => $uploadId, 'status' => 'completed']);
        $this->assertNotEmpty($course->fresh()->teaser_original_path);
        Queue::assertPushed(ProcessTeaserVideo::class, fn ($job) => $job->courseId === $course->id);
    }

    public function test_teacher_without_course_management_permission_cannot_start_teaser_upload(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());
        $course = $this->course();

        $this->actingAs($teacher)->post(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('video_upload_sessions', 0);
    }


    public function test_ready_teaser_is_rendered_as_a_real_public_video_player(): void
    {
        $course = $this->course();
        Storage::disk('public')->put('teasers/ready-preview.mp4', 'fake-video-content');
        $course->update([
            'teaser_path' => 'teasers/ready-preview.mp4',
            'teaser_status' => 'ready',
        ]);

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('video/mp4')
            ->assertSee('teasers/ready-preview.mp4');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
        return $admin;
    }

    private function course(): Course
    {
        return Course::query()->create([
            'title' => 'دوره تیزر آزمایشی',
            'slug' => 'teaser-test-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'status' => 'published',
        ]);
    }
}
