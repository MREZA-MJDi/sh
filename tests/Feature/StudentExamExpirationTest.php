<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use App\Services\StudentExamService;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class StudentExamExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_starting_again_expires_a_stale_attempt_and_consumes_one_allowed_attempt(): void
    {
        $teacher = $this->userWithRole('teacher');
        $student = $this->userWithRole('student');
        [$course, $classroom] = $this->enrolledClass($teacher, $student);

        $exam = Exam::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'آزمون زمان‌دار',
            'provider' => 'internal',
            'status' => 'published',
            'duration_minutes' => 20,
            'attempts_allowed' => 2,
        ]);
        Question::query()->create([
            'exam_id' => $exam->id,
            'type' => 'single',
            'question' => 'یک سؤال نمونه',
            'options' => ['الف', 'ب'],
            'correct_answer' => 'الف',
            'score' => 1,
            'sort_order' => 1,
        ]);

        $service = app(StudentExamService::class);
        $first = $service->start($student, $exam);

        Carbon::setTestNow(now()->addMinutes(21));
        try {
            $second = $service->start($student, $exam);
        } finally {
            Carbon::setTestNow();
        }

        $this->assertSame('expired', $first->fresh()->status);
        $this->assertSame(2, $second->attempt_number);
        $this->assertSame('in_progress', $second->status);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    /** @return array{0: Course, 1: Classroom} */
    private function enrolledClass(User $teacher, User $student): array
    {
        $course = Course::query()->create([
            'title' => 'دوره آزمون',
            'slug' => 'exam-expiration-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس آزمون',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        return [$course, $classroom];
    }
}
