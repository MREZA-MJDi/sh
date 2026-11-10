<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use App\Services\CourseProgressService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CourseLearningProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_progress_is_calculated_from_published_lessons_and_shown_to_student_and_guardian(): void
    {
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student->id);
        $teacher = $this->userWithRole('teacher');

        [$course, $classroom] = $this->courseAndClassroom($teacher);
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'فصل اول',
            'sort_order' => 1,
            'status' => 'published',
        ]);
        $completed = Lesson::query()->create([
            'course_section_id' => $section->id,
            'title' => 'درس اول',
            'sort_order' => 1,
            'status' => 'published',
        ]);
        $notStarted = Lesson::query()->create([
            'course_section_id' => $section->id,
            'title' => 'درس دوم',
            'sort_order' => 2,
            'status' => 'published',
        ]);
        Lesson::query()->create([
            'course_section_id' => $section->id,
            'title' => 'درس پیش‌نویس',
            'sort_order' => 3,
            'status' => 'draft',
        ]);

        LessonProgress::query()->create([
            'lesson_id' => $completed->id,
            'student_id' => $student->id,
            'progress_percent' => 100,
            'started_at' => now()->subDay(),
            'last_viewed_at' => now(),
            'completed_at' => now(),
        ]);

        $summary = app(CourseProgressService::class)->forStudent($student, $student->courseEnrollments()->select('course_id'))->first();
        $this->assertSame(2, (int) $summary->total_lessons);
        $this->assertSame(1, (int) $summary->completed_lessons);
        $this->assertSame(50, (int) $summary->progress_percent);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('پیشرفت دوره‌های من')
            ->assertSee('دوره پیشرفت');

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard'))
            ->assertOk()
            ->assertSee('پیشرفت دوره‌ها و درس‌ها')
            ->assertSee('دوره پیشرفت');

        $this->actingAs($student)
            ->patch(route('student.courses.lessons.progress', [$course, $notStarted]), [
                'progress_percent' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lesson_progress', [
            'lesson_id' => $notStarted->id,
            'student_id' => $student->id,
            'progress_percent' => 100,
        ]);

        $summary = app(CourseProgressService::class)->forStudent($student, $student->courseEnrollments()->select('course_id'))->first();
        $this->assertSame(100, (int) $summary->progress_percent);
    }

    public function test_student_cannot_open_course_or_mark_lesson_without_active_enrollment(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');
        [$course] = $this->courseAndClassroom($teacher);

        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'title' => 'بخش خصوصی',
            'status' => 'published',
        ]);
        $lesson = Lesson::query()->create([
            'course_section_id' => $section->id,
            'title' => 'درس خصوصی',
            'status' => 'published',
        ]);

        $this->actingAs($student)
            ->get(route('student.courses.show', $course))
            ->assertNotFound();

        $this->patch(route('student.courses.lessons.progress', [$course, $lesson]), [
            'progress_percent' => 100,
        ])->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    /** @return array{0: Course, 1: Classroom} */
    private function courseAndClassroom(User $teacher): array
    {
        $course = Course::query()->create([
            'title' => 'دوره پیشرفت',
            'slug' => 'course-progress-'.uniqid(),
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس پیشرفت',
            'status' => 'active',
        ]);

        return [$course, $classroom];
    }
}
