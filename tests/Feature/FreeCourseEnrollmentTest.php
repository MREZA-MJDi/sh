<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FreeCourseEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_active_student_can_enroll_in_published_free_course_idempotently(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
        $course = $this->course('free');

        $this->actingAs($student)->post(route('student.courses.enroll-free', $course))->assertRedirect(route('public.courses.show', $course->slug));
        $this->actingAs($student)->post(route('student.courses.enroll-free', $course))->assertRedirect(route('public.courses.show', $course->slug));

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'free',
            'status' => 'active',
        ]);
        $this->assertSame(1, CourseEnrollment::query()->where('course_id', $course->id)->where('student_id', $student->id)->count());
        $this->get(route('student.courses.index'))->assertOk()->assertSee($course->title);
    }

    public function test_student_cannot_self_enroll_in_paid_or_unpublished_course(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
        $paid = $this->course('paid');

        $this->actingAs($student)
            ->from(route('public.courses.show', $paid->slug))
            ->post(route('student.courses.enroll-free', $paid))
            ->assertRedirect(route('public.courses.show', $paid->slug))
            ->assertSessionHasErrors('course');

        $this->assertDatabaseMissing('course_enrollments', ['course_id' => $paid->id, 'student_id' => $student->id]);
    }

    private function course(string $type): Course
    {
        return Course::query()->create([
            'title' => 'دوره '.($type === 'free' ? 'رایگان' : 'پرداختی'),
            'slug' => 'course-'.$type.'-'.uniqid(),
            'type' => $type,
            'price' => $type === 'free' ? 0 : 100000,
            'status' => 'published',
        ]);
    }
}
