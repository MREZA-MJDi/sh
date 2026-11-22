<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentFreeCourseDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_can_discover_published_free_courses_without_an_existing_enrollment(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');
        $course = $this->course($teacher, [
            'title' => 'دوره رایگان قابل مشاهده برای همه دانش‌آموزان',
            'slug' => 'free-course-discovery-test',
            'type' => 'free',
            'price' => 0,
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.index'))
            ->assertOk()
            ->assertSeeText($course->title)
            ->assertSeeText('دسترسی فعال برای دانش‌آموزان')
            ->assertSeeText('شروع یادگیری')
            ->assertDontSeeText('ثبت‌نام رایگان و شروع')
            ->assertDontSeeText('ویژه دانش‌آموزان ثبت‌نام‌شده');
    }

    public function test_student_can_enroll_in_a_published_free_course_from_the_course_list(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');
        $course = $this->course($teacher, [
            'title' => 'دوره رایگان ثبت‌نام مستقیم',
            'slug' => 'free-course-enroll-test',
            'type' => 'free',
            'price' => 0,
        ]);

        $this->actingAs($student)
            ->post(route('student.courses.enroll-free', $course))
            ->assertRedirect(route('public.courses.show', $course->slug));

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'free',
            'status' => 'active',
        ]);
        $this->assertTrue(CourseEnrollment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->currentlyActive()
            ->exists());
    }

    public function test_logged_in_student_can_view_free_course_lesson_content_without_enrollment(): void
    {
        $teacher = $this->userWithRole('teacher');
        $course = $this->course($teacher, [
            'title' => 'دوره رایگان با سرفصل عمومی',
            'slug' => 'free-course-public-curriculum',
            'type' => 'free',
            'price' => 0,
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'سرفصل آزاد',
            'description' => 'معرفی عمومی محتوای دوره',
            'sort_order' => 1,
            'status' => 'published',
        ]);
        Lesson::query()->create([
            'course_section_id' => $section->id,
            'title' => 'درس آزاد بدون قفل',
            'body' => 'متن آموزشی عمومی دوره رایگان',
            'duration_minutes' => 20,
            'sort_order' => 1,
            'status' => 'published',
        ]);

        $this->actingAs($this->userWithRole('student'))
            ->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSeeText('درس آزاد بدون قفل')
            ->assertSeeText('متن آموزشی عمومی دوره رایگان')
            ->assertDontSeeText('ویژه دانش‌آموزان ثبت‌نام‌شده')
            ->assertDontSeeText('محتوای این درس پس از ثبت‌نام رایگان در دسترس خواهد بود.');
    }

    private function course(User $teacher, array $overrides = []): Course
    {
        return Course::query()->create(array_merge([
            'title' => 'دوره آزمایشی',
            'slug' => 'course-'.uniqid(),
            'description' => 'محتوای آزمایشی',
            'subject' => 'ریاضی',
            'grade' => 'دهم',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'skyroom',
            'learning_features' => [
                'recorded_video' => false,
                'live_class' => true,
                'resources' => true,
                'assignments' => true,
                'exams' => true,
                'projects' => false,
            ],
            'status' => 'published',
        ], $overrides));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

        return $user;
    }
}
