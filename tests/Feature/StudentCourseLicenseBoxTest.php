<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\SpotPlayerLicense;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentCourseLicenseBoxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_sees_only_their_active_course_license(): void
    {
        [$student, $course] = $this->enrolledStudentAndCourse();
        SpotPlayerLicense::query()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'license_key' => 'SP-TEST-KEY-001',
            'download_url' => 'https://player.example.test/install',
            'max_devices' => 2,
            'active_devices' => 1,
            'status' => 'active',
            'issued_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertRedirect(route('public.courses.show', $course->slug));

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('SP-TEST-KEY-001')
            ->assertDontSee('https://player.example.test/install');
    }

    public function test_student_without_a_license_sees_a_truthful_pending_state(): void
    {
        [$student, $course] = $this->enrolledStudentAndCourse();

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertRedirect(route('public.courses.show', $course->slug));

        $this->actingAs($student)
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertDontSee('کلید آزمایشی یا ساختگی صادر نمی‌شود.');
    }

    public function test_student_can_request_their_own_license_without_receiving_a_fake_active_key(): void
    {
        [$student, $course] = $this->enrolledStudentAndCourse();
        $otherStudent = User::factory()->create();
        $otherStudent->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

        $this->actingAs($student)
            ->post(route('student.courses.spotplayer-license.store', $course), ['student_id' => $otherStudent->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $license = SpotPlayerLicense::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $this->assertSame('waiting_contract', $license->status);
        $this->assertNull($license->license_key);
        $this->assertDatabaseMissing('spot_player_licenses', [
            'student_id' => $otherStudent->id,
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('integration_runs', [
            'provider' => 'spotplayer',
            'action' => 'issue_license',
            'status' => 'waiting_contract',
            'entity_id' => $license->id,
        ]);
    }

    public function test_student_cannot_request_a_license_for_a_course_without_active_enrollment(): void
    {
        $student = User::factory()->create(['phone' => '09120000000']);
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
        $course = Course::query()->create([
            'title' => 'دوره بدون ثبت‌نام',
            'slug' => 'student-license-no-enrollment',
            'type' => 'paid',
            'price' => 100000,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'spot-course-no-enrollment',
            'status' => 'published',
        ]);

        $this->actingAs($student)
            ->from(route('student.courses.index'))
            ->post(route('student.courses.spotplayer-license.store', $course))
            ->assertRedirect(route('student.courses.index'))
            ->assertSessionHasErrors('spotplayer');

        $this->assertDatabaseMissing('spot_player_licenses', [
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
    }

    private function enrolledStudentAndCourse(): array
    {
        $student = User::factory()->create(['phone' => '09120000000']);
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
        $course = Course::query()->create([
            'title' => 'دوره ویدیویی',
            'slug' => 'student-license-test-course',
            'type' => 'paid',
            'price' => 100000,
            'delivery_type' => 'spotplayer',
            'spotplayer_course_id' => 'spot-course-student-license',
            'status' => 'published',
        ]);
        CourseEnrollment::query()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now(),
        ]);

        return [$student, $course];
    }
}
