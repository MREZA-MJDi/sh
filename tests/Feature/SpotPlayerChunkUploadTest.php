<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Jobs\Learning\ProcessCourseRecordingVideo;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class SpotPlayerChunkUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_teacher_can_upload_a_self_hosted_course_video_for_their_class_using_chunks(): void
    {
        Queue::fake();
        $teacher = $this->userWithRole('teacher');
        $course = Course::query()->create([
            'title' => 'دوره ویدیویی مدرس',
            'slug' => 'teacher-spotplayer-chunk',
            'teacher_id' => null,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'spot-course-chunk',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس مدرس',
            'status' => 'active',
        ]);

        $start = $this->actingAs($teacher)->postJson(route('admin.uploads.start'), [
            'target' => 'spotplayer_video',
            'target_id' => $course->id,
            'total' => 1,
            'total_bytes' => 128,
            'title' => 'جلسه اول',
            'classroom_id' => $classroom->id,
        ])->assertOk();

        $uploadId = $start->json('upload_id');

        $this->postJson(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('part', str_repeat("\0", 128)),
        ])->assertOk();

        $complete = $this->postJson(route('admin.uploads.complete'), [
            'upload_id' => $uploadId,
            'total' => 1,
            'filename' => 'lesson-one.mp4',
        ])->assertOk()->assertJsonPath('status', 'processing');

        $video = SpotPlayerVideo::query()->findOrFail($complete->json('video_id'));

        $this->assertSame('processing', $video->status);
        Queue::assertPushed(ProcessCourseRecordingVideo::class, fn ($job) => $job->videoId === $video->id);
        $this->assertSame($classroom->id, $video->classroom_id);
        $this->assertStringStartsWith('private/course-videos/', $video->file_path);
        $this->assertSame(128, $video->file_size_bytes);
        $this->assertNotNull($video->file_sha256);
        Storage::disk('local')->assertExists($video->file_path);

        $this->assertDatabaseMissing('integration_runs', ['entity_id' => $video->id]);

        $this->postJson(route('admin.uploads.status'), ['upload_id' => $uploadId])
            ->assertOk()
            ->assertJsonPath('status', 'processing');
    }

    public function test_teacher_can_resume_an_interrupted_chunk_upload_without_reuploading_received_parts(): void
    {
        $teacher = $this->userWithRole('admin');
        $course = Course::query()->create([
            'title' => 'دوره ادامه آپلود',
            'slug' => 'teacher-video-resume',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'learning_features' => ['recorded_video' => true],
            'status' => 'draft',
        ]);

        $start = $this->actingAs($teacher)->postJson(route('admin.uploads.start'), [
            'target' => 'spotplayer_video',
            'target_id' => $course->id,
            'total_bytes' => 128,
            'title' => 'جلسه قابل ادامه',
        ])->assertOk();

        $uploadId = $start->json('upload_id');
        $this->postJson(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('part', str_repeat("\\0", 128)),
        ])->assertOk();

        $this->postJson(route('admin.uploads.resume'), ['upload_id' => $uploadId])
            ->assertOk()
            ->assertJsonPath('upload_id', $uploadId)
            ->assertJsonPath('total_chunks', 1)
            ->assertJsonPath('received_chunks.0', 0);
    }

    public function test_admin_can_upload_course_video_when_feature_matrix_is_enabled_without_legacy_delivery_type(): void
    {
        $admin = $this->userWithRole('admin');
        $course = Course::query()->create([
            'title' => 'دوره ویدیویی مستقل',
            'slug' => 'course-video-feature-matrix',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'learning_features' => ['recorded_video' => true],
            'status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.uploads.start'), [
                'target' => 'spotplayer_video',
                'target_id' => $course->id,
                'total' => 1,
                'total_bytes' => 128,
                'title' => 'ویدیوی جلسه اول',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    public function test_course_video_upload_rejects_files_larger_than_300_mb_before_creating_a_session(): void
    {
        $admin = $this->userWithRole('admin');
        $course = Course::query()->create([
            'title' => 'دوره با محدودیت حجم',
            'slug' => 'course-video-size-limit',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'learning_features' => ['recorded_video' => true],
            'status' => 'draft',
        ]);

        $this->actingAs($admin)->postJson(route('admin.uploads.start'), [
            'target' => 'spotplayer_video',
            'target_id' => $course->id,
            'total' => 51,
            'total_bytes' => 300 * 1024 * 1024 + 1,
            'title' => 'ویدیوی بیش از حد مجاز',
        ])->assertUnprocessable()->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('video_upload_sessions', 0);
        $this->assertSame(300, (int) config('learning.max_lesson_mb'));
    }

    public function test_teacher_cannot_start_video_upload_for_an_unassigned_course(): void
    {
        $teacher = $this->userWithRole('teacher');
        $owner = $this->userWithRole('teacher');
        $course = Course::query()->create([
            'title' => 'دوره مدرس دیگر',
            'slug' => 'other-teacher-spotplayer-chunk',
            'teacher_id' => $owner->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'spot-course-other',
            'status' => 'published',
        ]);

        $this->actingAs($teacher)->postJson(route('admin.uploads.start'), [
            'target' => 'spotplayer_video',
            'target_id' => $course->id,
            'total' => 1,
            'total_bytes' => 64,
            'title' => 'نباید ثبت شود',
        ])->assertNotFound();
    }

    public function test_upload_session_cannot_be_reused_by_another_teacher(): void
    {
        $owner = $this->userWithRole('teacher');
        $otherTeacher = $this->userWithRole('teacher');
        $course = Course::query()->create([
            'title' => 'دوره مالک نشست',
            'slug' => 'spotplayer-upload-session-owner',
            'teacher_id' => $owner->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'spot-course-session-owner',
            'status' => 'published',
        ]);

        $start = $this->actingAs($owner)->postJson(route('admin.uploads.start'), [
            'target' => 'spotplayer_video',
            'target_id' => $course->id,
            'total' => 1,
            'total_bytes' => 64,
            'title' => 'جلسه خصوصی',
        ])->assertOk();

        $uploadId = $start->json('upload_id');

        $this->actingAs($otherTeacher)->postJson(route('admin.uploads.chunk'), [
            'upload_id' => $uploadId,
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('part', str_repeat("\0", 64)),
        ])->assertNotFound();

        $this->actingAs($otherTeacher)->postJson(route('admin.uploads.complete'), [
            'upload_id' => $uploadId,
            'total' => 1,
            'filename' => 'private.mp4',
        ])->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));
        return $user;
    }
}
