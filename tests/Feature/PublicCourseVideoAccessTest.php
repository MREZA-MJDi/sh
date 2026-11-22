<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PublicCourseVideoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        config(['services.spotplayer.embed_hosts' => ['player.example.test']]);
    }

    private function makeStudent(): User
    {
        $student = User::factory()->create(['is_active' => true]);
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        return $student;
    }

    private function makeCourse(): Course
    {
        return Course::query()->create([
            'title' => 'دوره ویدیویی آزمایشی',
            'slug' => 'protected-video-course',
            'description' => 'توضیح عمومی دوره',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'status' => 'published',
        ]);
    }

    private function grantActiveEnrollment(User $student, Course $course, ?\DateTimeInterface $expiresAt = null): void
    {
        CourseEnrollment::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'source' => 'free',
            'status' => 'active',
            'granted_at' => now()->subMinute(),
            'expires_at' => $expiresAt,
        ]);
    }

    private function attachActiveProviderVideo(User $student, Course $course): void
    {
        SpotPlayerLicense::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'external_license_id' => 'license-'.$student->id.'-'.$course->id,
            'license_key' => 'provider-license-key',
            'status' => 'active',
            'issued_at' => now()->subMinute(),
        ]);

        SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'title' => 'جلسه اول دوره',
            'external_id' => 'provider-video-1',
            'embed_url' => 'https://player.example.test/embed/provider-video-1',
            'status' => 'active',
        ]);
    }

    public function test_guest_can_open_public_course_but_never_sees_the_video_player(): void
    {
        $course = $this->makeCourse();

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('دوره ویدیویی آزمایشی')
            ->assertDontSee('تماشای ویدیوهای دوره')
            ->assertDontSee('course-video-dialog');
    }

    public function test_free_course_lessons_are_available_to_authenticated_students_without_enrollment(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'فصل اول',
            'status' => 'published',
            'sort_order' => 1,
        ]);
        Lesson::query()->create([
            'course_section_id' => $section->id,
            'title' => 'درس مخصوص اعضا',
            'body' => 'محتوای درس پس از ثبت‌نام نمایش داده می‌شود.',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('محتوای درس پس از ثبت‌نام نمایش داده می‌شود.')
            ->assertDontSee('ویژه دانش‌آموزان ثبت‌نام‌شده')
            ->assertDontSee('ثبت‌نام رایگان');

        $this->assertDatabaseMissing('course_enrollments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_active_student_with_current_enrollment_and_provider_license_sees_same_page_player(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->grantActiveEnrollment($student, $course);
        $this->attachActiveProviderVideo($student, $course);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('تماشای ویدیوهای دوره')
            ->assertSee('course-video-dialog')
            ->assertSee('جلسه اول دوره')
            ->assertSee(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => SpotPlayerVideo::query()->where('course_id', $course->id)->value('id')]), false);
    }

    public function test_student_can_watch_manually_uploaded_free_course_video_without_enrollment_or_spotplayer_license(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $course->update([
            'delivery_type' => 'onsite',
            'learning_features' => ['recorded_video' => true],
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('private/course-videos/local-public-preview.mp4', 'video-content');

        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'title' => 'ویدیوی بارگذاری مستقیم',
            'status' => 'active',
            'file_path' => 'private/course-videos/local-public-preview.mp4',
            'file_original_name' => 'local-public-preview.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 13,
            'file_sha256' => hash('sha256', 'video-content'),
        ]);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('تماشای ویدیوهای دوره')
            ->assertSee('ویدیوی بارگذاری مستقیم');

        $this->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertRedirect(route('student.videos.show', $video));
    }

    public function test_active_class_member_can_see_its_session_recording_on_the_public_course_page(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->grantActiveEnrollment($student, $course);

        $teacher = User::factory()->create();
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس ریاضی',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه معادلات',
            'session_type' => 'online',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subDay()->addHour(),
            'meeting_provider' => 'jitsi',
            'status' => 'completed',
        ]);

        Storage::fake('local');
        Storage::disk('local')->put('private/course-videos/math-session.mp4', 'video-content');
        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'class_session_id' => $session->id,
            'title' => 'ویدیوی معادلات',
            'status' => 'active',
            'file_path' => 'private/course-videos/math-session.mp4',
            'file_original_name' => 'math-session.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 13,
            'file_sha256' => hash('sha256', 'video-content'),
        ]);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('ویدیوی معادلات')
            ->assertSee('جلسه معادلات');

        $this->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertRedirect(route('student.videos.show', $video));
    }

    public function test_course_enrollment_does_not_unlock_recordings_from_an_unassigned_classroom(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->grantActiveEnrollment($student, $course);

        $teacher = User::factory()->create();
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس اختصاصی',
            'status' => 'active',
        ]);
        Storage::fake('local');
        Storage::disk('local')->put('private/course-videos/class-only.mp4', 'private-video');
        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'title' => 'ویدیوی مخصوص کلاس دیگر',
            'status' => 'active',
            'file_path' => 'private/course-videos/class-only.mp4',
            'file_original_name' => 'class-only.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 13,
            'file_sha256' => hash('sha256', 'private-video'),
        ]);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('ویدیوی مخصوص کلاس دیگر');

        $this->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertForbidden();
    }

    public function test_student_without_enrollment_can_watch_free_course_provider_video_with_valid_license(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->attachActiveProviderVideo($student, $course);
        $video = SpotPlayerVideo::query()->where('course_id', $course->id)->firstOrFail();

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('تماشای ویدیوهای دوره')
            ->assertSee('course-video-dialog');

        $this->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertRedirect('https://player.example.test/embed/provider-video-1');
    }

    public function test_expired_paid_course_enrollment_does_not_receive_the_player(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $course->update(['type' => 'paid', 'price' => 100000]);
        $this->grantActiveEnrollment($student, $course, now()->subSecond());
        $this->attachActiveProviderVideo($student, $course);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('تماشای ویدیوهای دوره')
            ->assertDontSee('course-video-dialog');
    }

    public function test_every_play_request_rechecks_enrollment_status_before_redirecting_to_provider(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $course->update(['type' => 'paid', 'price' => 100000]);
        $this->grantActiveEnrollment($student, $course);
        $this->attachActiveProviderVideo($student, $course);
        $video = SpotPlayerVideo::query()->where('course_id', $course->id)->firstOrFail();

        $this->actingAs($student)
            ->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertRedirect('https://player.example.test/embed/provider-video-1')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->update(['status' => 'revoked']);

        $this->actingAs($student)
            ->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertForbidden();
    }

    public function test_revoked_provider_license_does_not_receive_the_player(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->grantActiveEnrollment($student, $course);
        $this->attachActiveProviderVideo($student, $course);
        SpotPlayerLicense::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->update(['status' => 'revoked']);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('تماشای ویدیوهای دوره')
            ->assertDontSee('course-video-dialog');
    }
    public function test_pending_teacher_video_is_hidden_from_public_course_and_cannot_be_played(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->grantActiveEnrollment($student, $course);

        Storage::fake('local');
        Storage::disk('local')->put('private/course-videos/pending.mp4', 'video-content');
        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'title' => 'ویدیوی در انتظار تأیید',
            'status' => 'active',
            'approval_status' => 'pending',
            'file_path' => 'private/course-videos/pending.mp4',
            'file_original_name' => 'pending.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 13,
            'file_sha256' => hash('sha256', 'video-content'),
        ]);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('ویدیوی در انتظار تأیید');

        $this->get(route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]))
            ->assertForbidden();
    }

}
