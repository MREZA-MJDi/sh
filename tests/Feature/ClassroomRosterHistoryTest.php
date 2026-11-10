<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Services\Admin\ClassroomService;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ClassroomRosterHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_removing_a_student_from_a_class_preserves_the_membership_row_and_revokes_roster_access(): void
    {
        $admin = User::factory()->create();
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::query()->create([
            'title' => 'دوره تاریخچه کلاس',
            'slug' => 'class-roster-history',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تاریخچه',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id, ['assigned_by' => $admin->id]);

        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'source' => 'classroom',
        ]);

        app(ClassroomService::class)->update($admin, $classroom, [
            'name' => $classroom->name,
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'meeting_url' => null,
            'status' => 'active',
            'schedule_days' => [],
            'schedule_time' => null,
            'duration_minutes' => 90,
            'student_ids' => [],
        ]);

        $this->assertDatabaseMissing('classroom_student', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
        $this->assertDatabaseHas('classroom_membership_history', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
            'removed_by' => $admin->id,
        ]);

        // A later re-enrollment creates a fresh active pivot row without
        // overwriting the immutable history record.
        app(ClassroomService::class)->update($admin, $classroom->fresh(), [
            'name' => $classroom->name,
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'meeting_url' => null,
            'status' => 'active',
            'schedule_days' => [],
            'schedule_time' => null,
            'duration_minutes' => 90,
            'student_ids' => [$student->id],
        ]);
        $this->assertDatabaseHas('classroom_student', [
            'classroom_id' => $classroom->id,
            'student_id' => $student->id,
        ]);
        $this->assertDatabaseCount('classroom_membership_history', 1);

        $this->assertFalse($classroom->fresh()->students()->whereKey($student->id)->exists());
        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'revoked',
            'source' => 'classroom',
        ]);
    }

    public function test_roster_sync_never_reactivates_a_revoked_payment_enrollment(): void
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::query()->create([
            'title' => 'دوره پرداختی',
            'slug' => 'revoked-payment-preservation',
            'teacher_id' => $teacher->id,
            'type' => 'paid',
            'price' => 100000,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس پرداختی',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        CourseEnrollment::query()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'payment',
            'status' => 'revoked',
            'source_reference' => 'payment-ref-revoked',
        ]);

        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $this->assertDatabaseHas('course_enrollments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'source' => 'payment',
            'status' => 'revoked',
            'source_reference' => 'payment-ref-revoked',
        ]);
    }
}
