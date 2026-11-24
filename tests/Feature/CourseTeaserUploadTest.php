<?php

namespace Tests\Feature;

use App\Jobs\Learning\FinalizeChunkUpload;
use App\Jobs\Learning\ProcessTeaserVideo;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Models\VideoUploadSession;
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

    public function test_private_upload_storage_supports_php_fpm_and_worker_group_access(): void
    {
        $disk = config('filesystems.disks.local');

        $this->assertSame('private', $disk['visibility']);
        $this->assertSame('private', $disk['directory_visibility']);
        $this->assertSame(0660, $disk['permissions']['file']['private']);
        $this->assertSame(0770, $disk['permissions']['dir']['private']);
    }

    public function test_new_teaser_upload_is_blocked_while_an_existing_session_is_finalizing(): void
    {
        $admin = $this->admin();
        $course = $this->course();
        $course->update(['teaser_status' => 'uploading']);

        VideoUploadSession::query()->create([
            'upload_id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $admin->id,
            'target' => 'teaser',
            'target_id' => $course->id,
            'status' => 'processing',
            'bytes_uploaded' => 64,
            'metadata' => [
                'total_chunks' => 1,
                'chunk_size_bytes' => 64,
                'total_bytes' => 64,
                'received_chunks' => ['0' => 64],
            ],
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($admin)->postJson(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
            'total_bytes' => 64,
        ])->assertStatus(409);

        $this->from(route('admin.courses.edit', $course))
            ->delete(route('admin.courses.teaser.destroy', $course))
            ->assertRedirect(route('admin.courses.edit', $course))
            ->assertSessionHasErrors('teaser');

        $this->assertDatabaseCount('video_upload_sessions', 1);
        $this->assertSame('uploading', $course->fresh()->teaser_status);
    }

    public function test_admin_can_upload_course_teaser_in_chunks_and_queue_processing(): void
    {
        $admin = $this->admin();
        $course = $this->course();

        $start = $this->actingAs($admin)->post(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
            'total' => 1,
            'total_bytes' => strlen(pack('N', 24).'ftypmp42'.pack('N', 0).'mp42isom'),
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

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'teaser_status' => 'uploading']);
        $this->assertDatabaseHas('video_upload_sessions', ['upload_id' => $uploadId, 'status' => 'processing']);
        Queue::assertPushed(FinalizeChunkUpload::class);

        $session = VideoUploadSession::query()->where('upload_id', $uploadId)->firstOrFail();
        (new FinalizeChunkUpload($session->id))->handle(app(\App\Services\SpotPlayer\SpotPlayerVideoService::class));

        $this->assertDatabaseHas('courses', ['id' => $course->id, 'teaser_status' => 'processing']);
        $this->assertDatabaseHas('video_upload_sessions', ['upload_id' => $uploadId, 'status' => 'completed']);
        $this->assertNotEmpty($course->fresh()->teaser_original_path);
        Queue::assertPushed(ProcessTeaserVideo::class, fn ($job) => $job->courseId === $course->id
            && $job->connection === 'video'
            && $job->queue === 'video');
    }

    public function test_chunk_retries_are_idempotent_and_resume_discards_truncated_chunks(): void
    {
        $admin = $this->admin();
        $course = $this->course();
        $payload = str_repeat('a', 64);

        $start = $this->actingAs($admin)->postJson(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
            'total_bytes' => strlen($payload),
        ])->assertOk();

        $uploadId = $start->json('upload_id');
        $chunk = UploadedFile::fake()->createWithContent('part.mp4', $payload);

        $this->postJson(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => $chunk,
        ])->assertOk();

        $this->postJson(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('part.mp4', $payload),
        ])->assertOk();

        $this->assertDatabaseHas('video_upload_sessions', [
            'upload_id' => $uploadId,
            'bytes_uploaded' => 64,
        ]);

        Storage::disk('local')->put("chunks/{$uploadId}/0.part", str_repeat('x', 12));

        $this->postJson(route('admin.uploads.resume'), [
            'upload_id' => $uploadId,
        ])->assertOk()->assertJsonPath('received_chunks', []);

        $this->assertDatabaseHas('video_upload_sessions', [
            'upload_id' => $uploadId,
            'bytes_uploaded' => 0,
        ]);
        Storage::disk('local')->assertMissing("chunks/{$uploadId}/0.part");
    }

    public function test_failed_teaser_finalization_preserves_chunks_without_publishing_a_broken_file(): void
    {
        $admin = $this->admin();
        $course = $this->course();
        $payload = str_repeat('not-a-video-', 8);

        $start = $this->actingAs($admin)->postJson(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
            'total_bytes' => strlen($payload),
        ])->assertOk();

        $uploadId = $start->json('upload_id');

        $this->postJson(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('part.mp4', $payload),
        ])->assertOk();

        $this->postJson(route('admin.uploads.complete'), [
            'upload_id' => $uploadId,
            'total' => 1,
            'filename' => 'course-teaser.mp4',
        ])->assertOk()->assertJsonPath('status', 'processing');

        $session = VideoUploadSession::query()->where('upload_id', $uploadId)->firstOrFail();
        $job = new FinalizeChunkUpload($session->id);
        try {
            $job->handle(app(\App\Services\SpotPlayer\SpotPlayerVideoService::class));
            $this->fail('Invalid teaser content should fail during queued finalization.');
        } catch (\RuntimeException $exception) {
            $job->failed($exception);
        }

        $this->assertDatabaseHas('video_upload_sessions', [
            'upload_id' => $uploadId,
            'status' => 'failed',
            'bytes_uploaded' => strlen($payload),
        ]);
        Storage::disk('local')->assertExists("chunks/{$uploadId}/0.part");
        Storage::disk('local')->assertMissing("uploads/{$uploadId}.mp4");
        $this->assertSame('failed', $course->fresh()->teaser_status);
    }

    public function test_teaser_upload_rejects_files_larger_than_200_mb_before_creating_a_session(): void
    {
        $admin = $this->admin();
        $course = $this->course();

        $this->actingAs($admin)->postJson(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
            'total' => 41,
            'total_bytes' => 200 * 1024 * 1024 + 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('video_upload_sessions', 0);
        $this->assertSame(200, (int) config('learning.max_teaser_mb'));
    }

    public function test_teacher_without_course_management_permission_cannot_start_teaser_upload(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->firstOrFail());
        $course = $this->course();

        $this->actingAs($teacher)->post(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
            'total' => 1,
            'total_bytes' => 100,
        ])->assertForbidden();

        $this->assertDatabaseCount('video_upload_sessions', 0);
    }


    public function test_missing_teaser_upload_session_returns_actionable_json_instead_of_model_not_found(): void
    {
        $admin = $this->admin();
        $course = $this->course();

        $start = $this->actingAs($admin)->postJson(route('admin.uploads.start'), [
            'target' => 'teaser',
            'target_id' => $course->id,
            'total' => 1,
            'total_bytes' => 64,
        ])->assertOk();

        $uploadId = $start->json('upload_id');
        \App\Models\VideoUploadSession::query()->where('upload_id', $uploadId)->delete();

        $this->postJson(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('part.mp4', str_repeat('x', 64)),
        ])->assertStatus(410)
            ->assertJsonPath('message', 'نشست آپلود پیدا نشد یا از سامانه حذف شده است؛ لطفاً بارگذاری را دوباره آغاز کنید.');
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
