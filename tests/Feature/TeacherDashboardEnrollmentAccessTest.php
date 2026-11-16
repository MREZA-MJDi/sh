<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\DashboardService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherDashboardEnrollmentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_teacher_dashboard_excludes_students_with_expired_course_access(): void
    {
        $teacher = $this->userWithRole('teacher');
        $expiredStudent = $this->userWithRole('student');
        $activeStudent = $this->userWithRole('student');

        $course = Course::query()->create([
            'title' => 'دوره تخصیص‌یافته به مدرس',
            'slug' => 'teacher-dashboard-enrollment-scope',
            'teacher_id' => $teacher->id,
            'type' => 'paid',
            'price' => 500000,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        CourseEnrollment::query()->create([
            'course_id' => $course->id,
            'student_id' => $expiredStudent->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now()->subMonth(),
            'expires_at' => now()->subMinute(),
        ]);

        CourseEnrollment::query()->create([
            'course_id' => $course->id,
            'student_id' => $activeStudent->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now(),
        ]);

        $dashboard = app(DashboardService::class)->build($teacher);

        $this->assertSame(1, $dashboard['stats']['students']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }
}
