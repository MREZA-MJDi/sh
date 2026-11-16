<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminCourseIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_course_index_counts_only_current_active_access_and_separates_pending_payment(): void
    {
        $admin = $this->userWithRole('admin');
        $activeStudent = $this->userWithRole('student');
        $expiredStudent = $this->userWithRole('student');
        $pendingStudent = $this->userWithRole('student');

        $course = Course::query()->create([
            'title' => 'دوره شمارش دسترسی',
            'slug' => 'course-access-counts',
            'type' => 'paid',
            'price' => 500000,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        CourseEnrollment::query()->create([
            'course_id' => $course->id,
            'student_id' => $activeStudent->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now(),
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
            'student_id' => $pendingStudent->id,
            'source' => 'classroom',
            'status' => 'pending_payment',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.courses.index'))
            ->assertOk()
            ->assertViewHas('courses', function ($courses) use ($course): bool {
                $listedCourse = $courses->firstWhere('id', $course->id);

                return $listedCourse !== null
                    && (int) $listedCourse->active_enrollments_count === 1
                    && (int) $listedCourse->pending_payment_enrollments_count === 1;
            });
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }
}
