<?php

namespace App\Services\Courses;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseEnrollmentService
{
    public function syncClassroomRoster(Classroom $classroom): void
    {
        $classroom->loadMissing('course');

        if ($classroom->course) {
            $this->syncCourseRoster($classroom->course);
        }
    }

    /**
     * Reconcile course access from the active classroom roster in a bounded
     * number of queries. Verified payment records are never rewritten here.
     */
    public function syncCourseRoster(Course $course): void
    {
        $studentIds = DB::table('classroom_student')
            ->join('classrooms', 'classrooms.id', '=', 'classroom_student.classroom_id')
            ->where('classrooms.course_id', $course->id)
            ->where('classrooms.status', 'active')
            ->whereNull('classroom_student.left_at')
            ->distinct()
            ->pluck('classroom_student.student_id')
            ->map(static fn ($id): int => (int) $id)
            ->values();

        DB::transaction(function () use ($course, $studentIds): void {
            $classroomStatus = match (true) {
                $course->status !== 'published' => 'suspended',
                $course->type === 'free' => 'active',
                default => 'pending_payment',
            };

            if ($studentIds->isNotEmpty()) {
                CourseEnrollment::query()
                    ->where('course_id', $course->id)
                    ->where('source', 'classroom')
                    ->whereIn('student_id', $studentIds->all())
                    ->update(['status' => $classroomStatus]);
            }

            $outOfRoster = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('source', 'classroom');

            if ($studentIds->isNotEmpty()) {
                $outOfRoster->whereNotIn('student_id', $studentIds->all());
            }

            // Keep the original grant timestamp as audit history when access is revoked.
            $outOfRoster->update(['status' => 'revoked']);

            if ($studentIds->isEmpty()) {
                return;
            }

            $existingIds = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->whereIn('student_id', $studentIds->all())
                ->pluck('student_id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $newStudentIds = $studentIds->diff($existingIds)->values();
            if ($newStudentIds->isEmpty()) {
                return;
            }

            $now = now();
            $rows = $newStudentIds->map(static fn (int $studentId): array => [
                'course_id' => $course->id,
                'student_id' => $studentId,
                'source' => 'classroom',
                'status' => $classroomStatus,
                'granted_by' => auth()->id(),
                'source_reference' => 'course:'.$course->id.':classroom-roster',
                'granted_at' => $classroomStatus === 'active' ? $now : null,
                'expires_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('course_enrollments')->insertOrIgnore($rows);
        }, 3);
    }

    /**
     * Call only after the payment provider has verified a successful payment.
     */
    public function grantAfterVerifiedPayment(
        User $student,
        Course $course,
        string $paymentReference
    ): CourseEnrollment {
        if (! $student->hasRole('student') || ! $student->is_active) {
            throw ValidationException::withMessages(['student' => 'حساب دانش‌آموز فعال و معتبر نیست.']);
        }

        if ($course->type !== 'paid' || $course->status !== 'published') {
            throw ValidationException::withMessages(['course' => 'دسترسی پرداختی فقط برای دوره پولی منتشرشده ایجاد می‌شود.']);
        }

        return DB::transaction(function () use ($student, $course, $paymentReference): CourseEnrollment {
            $enrollment = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if (! $enrollment) {
                $enrollment = new CourseEnrollment([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                ]);
            }

            $enrollment->fill([
                'source' => 'payment',
                'status' => 'active',
                'source_reference' => $paymentReference,
                'granted_at' => now(),
            ])->save();

            return $enrollment;
        }, 3);
    }

    public function hasActiveAccess(User $student, Course $course): bool
    {
        if ($course->status !== 'published') {
            return false;
        }

        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
