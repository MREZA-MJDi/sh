<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherCourseContentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_can_manage_sections_and_lessons_only_for_assigned_course(): void
    {
        $teacher = $this->userWithRole('teacher');
        $otherTeacher = $this->userWithRole('teacher');
        $course = $this->courseFor($teacher, 'teacher-managed-content');
        $otherCourse = $this->courseFor($otherTeacher, 'other-teacher-content');

        $this->actingAs($teacher)
            ->get(route('admin.courses.content', $course))
            ->assertOk()
            ->assertSee('افزودن بخش جدید');

        $this->actingAs($teacher)
            ->post(route('admin.courses.sections.store', $course), [
                'title' => 'فصل اول',
                'sort_order' => 1,
                'status' => 'published',
            ])
            ->assertRedirect();

        $section = CourseSection::query()->where('course_id', $course->id)->firstOrFail();

        $this->actingAs($teacher)
            ->post(route('admin.courses.lessons.store', $section), [
                'title' => 'درس اول',
                'body' => 'متن درس',
                'duration_minutes' => 20,
                'sort_order' => 1,
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'course_section_id' => $section->id,
            'title' => 'درس اول',
            'status' => 'published',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.courses.content', $otherCourse))
            ->assertNotFound();

        $this->actingAs($teacher)
            ->post(route('admin.courses.sections.store', $otherCourse), [
                'title' => 'بخش غیرمجاز',
                'status' => 'published',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('course_sections', [
            'course_id' => $otherCourse->id,
            'title' => 'بخش غیرمجاز',
        ]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    private function courseFor(User $teacher, string $slug): Course
    {
        return Course::query()->create([
            'title' => 'دوره مدرس',
            'slug' => $slug,
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
    }

    public function test_course_sections_and_lessons_render_in_their_configured_order(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        $course = $this->courseFor($teacher, 'ordered-curriculum');

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس محتوای مرتب‌شده',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $secondSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'فصل دوم',
            'status' => 'published',
            'sort_order' => 2,
        ]);
        $firstSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'فصل اول',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        Lesson::query()->create([
            'course_section_id' => $secondSection->id,
            'title' => 'درس دوم',
            'status' => 'published',
            'sort_order' => 2,
        ]);
        Lesson::query()->create([
            'course_section_id' => $secondSection->id,
            'title' => 'درس اول',
            'status' => 'published',
            'sort_order' => 1,
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.courses.content', $course))
            ->assertOk()
            ->assertSeeInOrder(['فصل اول', 'فصل دوم', 'درس اول', 'درس دوم']);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertOk()
            ->assertSeeInOrder(['فصل اول', 'فصل دوم', 'درس اول', 'درس دوم']);
    }

}
