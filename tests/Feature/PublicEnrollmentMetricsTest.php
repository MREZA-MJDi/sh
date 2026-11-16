<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicEnrollmentMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_public_course_and_home_metrics_exclude_expired_and_pending_enrollments(): void
    {
        $course = Course::query()->create([
            'title' => 'دوره با ثبت‌نام‌های متفاوت',
            'slug' => 'course-enrollment-metrics',
            'type' => 'paid',
            'price' => 450000,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $currentStudent = User::factory()->create();
        $expiredStudent = User::factory()->create();
        $pendingStudent = User::factory()->create();

        $course->enrollments()->create([
            'student_id' => $currentStudent->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now(),
        ]);

        $course->enrollments()->create([
            'student_id' => $expiredStudent->id,
            'source' => 'manual',
            'status' => 'active',
            'granted_at' => now()->subMonth(),
            'expires_at' => now()->subMinute(),
        ]);

        $course->enrollments()->create([
            'student_id' => $pendingStudent->id,
            'source' => 'classroom',
            'status' => 'pending_payment',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats): bool => $stats['enrollments'] === 1)
            ->assertViewHas('courses', function ($courses) use ($course): bool {
                $listedCourse = $courses->firstWhere('id', $course->id);

                return $listedCourse !== null && (int) $listedCourse->enrollments_count === 1;
            });

        $this->get(route('public.courses.index'))
            ->assertOk()
            ->assertViewHas('courses', function ($courses) use ($course): bool {
                $listedCourse = $courses->firstWhere('id', $course->id);

                return $listedCourse !== null && (int) $listedCourse->enrollments_count === 1;
            });

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('1 دانش‌آموز');
    }
}
