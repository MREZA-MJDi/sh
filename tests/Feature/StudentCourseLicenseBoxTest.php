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
            ->assertOk()
            ->assertSee('لایسنس SpotPlayer')
            ->assertSee('SP-TEST-KEY-001')
            ->assertSee('https://player.example.test/install');
    }

    public function test_student_without_a_license_sees_a_truthful_pending_state(): void
    {
        [$student, $course] = $this->enrolledStudentAndCourse();

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertSee('ثبت‌نشده')
            ->assertSee('کلید آزمایشی یا ساختگی صادر نمی‌شود.');
    }

    private function enrolledStudentAndCourse(): array
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
        $course = Course::query()->create([
            'title' => 'دوره ویدیویی',
            'slug' => 'student-license-test-course',
            'type' => 'paid',
            'price' => 100000,
            'delivery_type' => 'spotplayer',
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
