<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassSessionAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_session_owns_attendance_records_and_preserves_classroom_relation(): void
    {
        [$teacher, $student, $classroom] = $this->classroomFixture();

        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه اول',
            'session_type' => 'online',
            'starts_at' => now()->startOfDay()->addHours(16),
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);

        $attendance = Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'session_date' => now()->toDateString(),
            'status' => 'present',
            'marked_by' => $teacher->id,
        ]);

        $this->assertSame($classroom->id, $session->fresh()->classroom->id);
        $this->assertTrue($session->fresh()->attendances->contains('id', $attendance->id));
        $this->assertSame($session->id, $attendance->fresh()->classSession->id);
    }

    public function test_one_student_cannot_have_duplicate_attendance_in_the_same_session(): void
    {
        [$teacher, $student, $classroom] = $this->classroomFixture();

        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه یکتا',
            'session_type' => 'onsite',
            'starts_at' => now(),
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);

        $attributes = [
            'classroom_id' => $classroom->id,
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'session_date' => now()->toDateString(),
            'status' => 'present',
            'marked_by' => $teacher->id,
        ];

        Attendance::query()->create($attributes);

        $this->expectException(QueryException::class);
        Attendance::query()->create($attributes);
    }

    public function test_class_session_cannot_be_deleted_while_attendance_is_linked(): void
    {
        [$teacher, $student, $classroom] = $this->classroomFixture();

        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه دارای سابقه حضور',
            'session_type' => 'online',
            'starts_at' => now(),
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);

        Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'session_date' => now()->toDateString(),
            'status' => 'present',
            'marked_by' => $teacher->id,
        ]);

        $this->expectException(QueryException::class);
        $session->delete();
    }

    /** @return array{0: User, 1: User, 2: Classroom} */
    private function classroomFixture(): array
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::query()->create([
            'title' => 'دوره جلسات',
            'slug' => 'class-session-attendance-test',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس جلسات',
            'status' => 'active',
        ]);

        $classroom->students()->attach($student->id);

        return [$teacher, $student, $classroom];
    }
}
