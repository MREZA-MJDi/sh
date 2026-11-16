<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\SpotPlayerLicense;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('https://player.example.test/embed/provider-video-1', false);
    }

    public function test_student_without_enrollment_does_not_receive_the_player_even_with_a_provider_license(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->attachActiveProviderVideo($student, $course);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('تماشای ویدیوهای دوره')
            ->assertDontSee('course-video-dialog');
    }

    public function test_expired_enrollment_does_not_receive_the_player(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->grantActiveEnrollment($student, $course, now()->subSecond());
        $this->attachActiveProviderVideo($student, $course);

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('تماشای ویدیوهای دوره')
            ->assertDontSee('course-video-dialog');
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
}
