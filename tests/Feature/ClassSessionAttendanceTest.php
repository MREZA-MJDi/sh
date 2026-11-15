<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\Admin\AttendanceService;
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

    public function test_same_student_can_have_attendance_for_two_sessions_on_the_same_day(): void
    {
        [$teacher, $student, $classroom] = $this->classroomFixture();
        $date = now()->toDateString();

        $morning = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه صبح',
            'session_type' => 'onsite',
            'starts_at' => now()->startOfDay()->addHours(9),
            'ends_at' => now()->startOfDay()->addHours(10),
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);
        $afternoon = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه عصر',
            'session_type' => 'onsite',
            'starts_at' => now()->startOfDay()->addHours(15),
            'ends_at' => now()->startOfDay()->addHours(16),
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);

        foreach ([$morning, $afternoon] as $session) {
            Attendance::query()->create([
                'classroom_id' => $classroom->id,
                'class_session_id' => $session->id,
                'student_id' => $student->id,
                'session_date' => $date,
                'status' => 'present',
                'marked_by' => $teacher->id,
            ]);
        }

        $this->assertSame(2, Attendance::query()->where('student_id', $student->id)->whereDate('session_date', $date)->count());
    }

    public function test_attendance_service_persists_explicit_duration_and_computes_lateness_from_actual_check_in(): void
    {
        [$teacher, $student, $classroom] = $this->classroomFixture();
        $startsAt = now()->subHours(2);
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه با زمان مشخص',
            'session_type' => 'online',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(90),
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);

        app(AttendanceService::class)->record($classroom, $session, [
            $student->id => [
                'status' => 'late',
                'joined_at' => $startsAt->copy()->addMinutes(12)->format('Y-m-d\\TH:i'),
                'attended_minutes' => 70,
            ],
        ], $teacher->id);

        $record = Attendance::query()->with('session')->where('class_session_id', $session->id)->firstOrFail();

        $this->assertSame(90, $record->scheduled_minutes);
        $this->assertSame(70, $record->attended_minutes);
        $this->assertSame(12, $record->late_minutes);
    }

    public function test_legacy_attendance_without_real_session_end_does_not_invent_a_scheduled_duration(): void
    {
        [$teacher, $student, $classroom] = $this->classroomFixture();
        $session = ClassSession::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'جلسه قدیمی',
            'session_type' => 'legacy',
            'starts_at' => now()->startOfDay(),
            'ends_at' => null,
            'status' => 'completed',
            'created_by' => $teacher->id,
        ]);

        $record = Attendance::query()->create([
            'classroom_id' => $classroom->id,
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'session_date' => now()->toDateString(),
            'status' => 'present',
            'marked_by' => $teacher->id,
        ])->load('session');

        $this->assertNull($record->scheduled_minutes);
        $this->assertNull($record->late_minutes);
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
