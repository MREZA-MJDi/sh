<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearningResource;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regression tests for preventing course media and paid learning material leakage.
 *
 * These tests intentionally exercise the HTTP boundary, not only model scopes:
 * a user must not be able to bypass the UI by guessing a resource/video ID.
 */
final class MediaLeakageSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_raw_spotplayer_video_is_never_streamed_even_to_an_enrolled_student(): void
    {
        [$student, $teacher, $course, $classroom] = $this->makeCourseAndStudent();
        Storage::disk('local')->put('private/spotplayer-staging/secret.mp4', 'CONFIDENTIAL-RAW-VIDEO');

        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'ویدیوی محرمانه',
            'status' => 'waiting_contract',
            'file_path' => 'private/spotplayer-staging/secret.mp4',
            'file_original_name' => 'secret.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 22,
            'file_sha256' => hash('sha256', 'CONFIDENTIAL-RAW-VIDEO'),
            'staged_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.videos.stream', $video));

        $response->assertStatus(409)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDontSee('CONFIDENTIAL-RAW-VIDEO');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));

        Storage::disk('public')->assertMissing('private/spotplayer-staging/secret.mp4');
        Storage::disk('local')->assertExists('private/spotplayer-staging/secret.mp4');
    }

    public function test_video_id_guessing_does_not_grant_access_to_a_non_member(): void
    {
        [$student, $teacher, $course, $classroom] = $this->makeCourseAndStudent(enroll: false);
        Storage::disk('local')->put('private/spotplayer-staging/other-class.mp4', 'OTHER-CLASS-VIDEO');

        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'ویدیوی کلاس دیگر',
            'status' => 'waiting_contract',
            'file_path' => 'private/spotplayer-staging/other-class.mp4',
            'file_original_name' => 'other-class.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 17,
            'file_sha256' => hash('sha256', 'OTHER-CLASS-VIDEO'),
            'staged_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.videos.show', $video))
            ->assertNotFound();

        $this->get(route('student.videos.stream', $video))
            ->assertNotFound()
            ->assertDontSee('OTHER-CLASS-VIDEO');
    }

    public function test_course_enrollment_does_not_bypass_a_resource_specific_classroom_restriction(): void
    {
        [$student, $teacher, $course, $enrolledClassroom] = $this->makeCourseAndStudent();

        // The student is enrolled in the course through classroom A, but the
        // material is explicitly assigned to classroom B.
        $otherClassroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس دیگر',
            'status' => 'active',
        ]);

        $resource = $this->makeResource($course, $otherClassroom);
        Storage::disk('local')->put($resource->file_path, 'CLASSROOM-RESTRICTED-HANDOUT');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound()
            ->assertDontSee('CLASSROOM-RESTRICTED-HANDOUT');
    }

    public function test_expired_course_enrollment_cannot_download_learning_resource(): void
    {
        [$student, , $course, $classroom] = $this->makeCourseAndStudent();
        $enrollment = CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->firstOrFail();
        $enrollment->forceFill(['expires_at' => now()->subSecond()])->save();

        $resource = $this->makeResource($course, $classroom);
        Storage::disk('local')->put($resource->file_path, 'EXPIRED-COURSE-HANDOUT');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound()
            ->assertDontSee('EXPIRED-COURSE-HANDOUT');
    }

    public function test_suspended_enrollment_cannot_download_learning_resource(): void
    {
        [$student, , $course, $classroom] = $this->makeCourseAndStudent();
        CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->update(['status' => 'suspended']);

        $resource = $this->makeResource($course, $classroom);
        Storage::disk('local')->put($resource->file_path, 'SUSPENDED-COURSE-HANDOUT');

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound()
            ->assertDontSee('SUSPENDED-COURSE-HANDOUT');
    }

    public function test_unpublished_course_resource_is_not_available_even_if_student_was_previously_enrolled(): void
    {
        [$student, , $course, $classroom] = $this->makeCourseAndStudent();
        $resource = $this->makeResource($course, $classroom);
        Storage::disk('local')->put($resource->file_path, 'UNPUBLISHED-COURSE-HANDOUT');

        $course->update(['status' => 'draft']);

        $this->actingAs($student)
            ->get(route('student.resources.download', $resource))
            ->assertNotFound()
            ->assertDontSee('UNPUBLISHED-COURSE-HANDOUT');
    }

    private function makeCourseAndStudent(bool $enroll = true): array
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');

        $course = Course::query()->create([
            'title' => 'دوره محرمانه',
            'slug' => 'media-security-'.uniqid(),
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس محرمانه',
            'status' => 'active',
        ]);

        if ($enroll) {
            $classroom->students()->attach($student->id);
            app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);
        }

        return [$student, $teacher, $course, $classroom];
    }

    private function makeResource(Course $course, Classroom $classroom): LearningResource
    {
        return LearningResource::query()->create([
            'classroom_id' => $classroom->id,
            'course_id' => $course->id,
            'uploaded_by' => $classroom->teacher_id,
            'title' => 'جزوه محرمانه دوره',
            'kind' => 'pdf',
            'is_free' => true,
            'price' => 0,
            'visibility' => 'enrolled',
            'file_path' => 'private/resources/confidential-handout.pdf',
            'status' => 'published',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
