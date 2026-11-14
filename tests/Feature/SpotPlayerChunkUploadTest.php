<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_teacher_can_stage_private_spotplayer_video_for_a_class_they_teach_using_chunks(): void
    {
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
        ])->assertOk()->assertJsonPath('status', 'waiting_contract');

        $video = SpotPlayerVideo::query()->findOrFail($complete->json('video_id'));

        $this->assertSame('waiting_contract', $video->status);
        $this->assertSame($classroom->id, $video->classroom_id);
        $this->assertStringStartsWith('private/spotplayer-staging/', $video->file_path);
        $this->assertSame(128, $video->file_size_bytes);
        $this->assertNotNull($video->file_sha256);
        Storage::disk('local')->assertExists($video->file_path);

        $this->assertDatabaseHas('integration_runs', [
            'provider' => 'spotplayer',
            'action' => 'upload_video',
            'status' => 'waiting_contract',
            'entity_id' => $video->id,
        ]);
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
            'title' => 'نباید ثبت شود',
        ])->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));
        return $user;
    }
}
