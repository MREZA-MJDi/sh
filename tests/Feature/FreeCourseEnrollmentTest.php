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

    public function test_guest_returns_to_the_same_free_course_after_creating_a_student_account(): void
    {
        $course = $this->course('free');
        $courseUrl = route('public.courses.show', $course->slug);
        $registerUrl = route('register', ['course' => $course->slug]);
        $this->get($courseUrl)
            ->assertOk()
            ->assertSee(route('login', ['course' => $course->slug]))
            ->assertSee($registerUrl);
        $this->get($registerUrl)->assertOk()->assertSee('name="return_course"', false);

        $this->post(route('register.store'), [
            'name' => 'دانش‌آموز دوره رایگان',
            'email' => 'free-course-return@example.test',
            'password' => 'ExamplePass123!',
            'password_confirmation' => 'ExamplePass123!',
            'return_course' => $course->slug,
        ])->assertRedirect($courseUrl);

        $this->get($courseUrl)->assertOk()->assertSee('این دوره برایت باز است')
            ->assertDontSee('ورود و شروع رایگان');
    }

    public function test_existing_student_returns_to_the_same_free_course_after_login(): void
    {
        $course = $this->course('free');
        $student = User::factory()->create(['password' => 'ExamplePass123!', 'is_active' => true]);
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());

        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'ExamplePass123!',
            'return_course' => $course->slug,
        ])->assertRedirect(route('public.courses.show', $course->slug));
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

    public function test_published_free_course_is_accessible_before_any_student_enrolls(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->firstOrFail());
        $free = $this->course('free');
        $paid = $this->course('paid');

        $this->assertDatabaseCount('course_enrollments', 0);
        $this->actingAs($student)
            ->get(route('student.courses.show', $free))
            ->assertRedirect(route('public.courses.show', $free->slug));
        $this->get(route('student.courses.show', $paid))->assertNotFound();
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
