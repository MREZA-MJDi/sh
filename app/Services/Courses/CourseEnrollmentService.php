<?php

namespace App\Services\Courses;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use App\Services\Learning\LearningAccessService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseEnrollmentService
{
    public function __construct(private readonly LearningAccessService $learningAccess) {}
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
        DB::transaction(function () use ($course): void {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            $studentIds = DB::table('classroom_student')
                ->join('classrooms', 'classrooms.id', '=', 'classroom_student.classroom_id')
                ->where('classrooms.course_id', $course->id)
                ->where('classrooms.status', 'active')
                ->distinct()
                ->pluck('classroom_student.student_id')
                ->map(static fn ($id): int => (int) $id)
                ->values();

            if ($course->status !== 'published') {
                CourseEnrollment::query()
                    ->where('course_id', $course->id)
                    ->where('source', 'payment')
                    ->where('status', 'active')
                    ->update(['status' => 'suspended']);
            } else {
                // Restore previously granted payment access when the course is republished.
                // Revoked enrollments remain revoked and are never reactivated by roster sync.
                CourseEnrollment::query()
                    ->where('course_id', $course->id)
                    ->where('source', 'payment')
                    ->where('status', 'suspended')
                    ->update(['status' => 'active']);
            }

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

        return DB::transaction(function () use ($student, $course, $paymentReference): CourseEnrollment {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            if ($course->type !== 'paid' || $course->status !== 'published') {
                throw ValidationException::withMessages(['course' => 'دسترسی پرداختی فقط برای دوره پولی منتشرشده ایجاد می‌شود.']);
            }

            $enrollment = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment?->source === 'payment' && $enrollment->source_reference === $paymentReference) {
                return $enrollment;
            }

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
                'expires_at' => null,
            ])->save();

            return $enrollment;
        }, 3);
    }


    public function enrollFreeCourse(User $student, Course $course): CourseEnrollment
    {
        if (! $student->hasRole('student') || ! $student->is_active) {
            throw ValidationException::withMessages(['student' => 'فقط دانش‌آموز فعال می‌تواند در دوره ثبت‌نام کند.']);
        }

        return DB::transaction(function () use ($student, $course): CourseEnrollment {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            if ($course->status !== 'published' || $course->type !== 'free') {
                throw ValidationException::withMessages(['course' => 'ثبت‌نام مستقیم فقط برای دوره رایگان منتشرشده فعال است.']);
            }

            $enrollment = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment && $enrollment->status === 'active'
                && (! $enrollment->expires_at || $enrollment->expires_at->isFuture())) {
                return $enrollment;
            }

            if ($enrollment?->source === 'payment') {
                throw ValidationException::withMessages(['course' => 'وضعیت ثبت‌نام پرداختی این دوره باید توسط پشتیبانی بررسی شود.']);
            }

            if (! $enrollment) {
                $enrollment = new CourseEnrollment([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                ]);
            }

            $enrollment->fill([
                'source' => 'free',
                'status' => 'active',
                'granted_by' => null,
                'source_reference' => 'free-course:'.$course->id,
                'granted_at' => now(),
                'expires_at' => null,
            ])->save();

            return $enrollment->refresh();
        }, 3);
    }

    /** Grant an administrative enrollment without fabricating a payment. */
    public function grantManually(User $student, Course $course, User $actor, ?string $expiresAt = null): CourseEnrollment
    {
        if (! $student->is_active || ! $student->hasRole('student')) {
            throw ValidationException::withMessages(['student_id' => 'فقط دانش‌آموز فعال می‌تواند ثبت‌نام شود.']);
        }

        return DB::transaction(function () use ($student, $course, $actor, $expiresAt): CourseEnrollment {
            $course = Course::query()->lockForUpdate()->findOrFail($course->id);
            if ($course->status !== 'published') {
                throw ValidationException::withMessages(['course_id' => 'ثبت‌نام دستی فقط برای دوره منتشرشده انجام می‌شود.']);
            }

            $enrollment = CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->first();

            if ($enrollment?->source === 'payment' && in_array($enrollment->status, ['active', 'suspended', 'revoked'], true)) {
                throw ValidationException::withMessages(['student_id' => 'ثبت‌نام پرداختی موجود است و نباید با ثبت دستی بازنویسی شود.']);
            }

            $enrollment ??= new CourseEnrollment(['course_id' => $course->id, 'student_id' => $student->id]);
            $enrollment->fill([
                'source' => 'manual',
                'status' => 'active',
                'granted_by' => $actor->id,
                'source_reference' => 'manual:'.$actor->id,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
            ])->save();

            return $enrollment->refresh();
        }, 3);
    }

    public function hasActiveAccess(User $student, Course $course): bool
    {
        return $this->learningAccess->hasCourseAccess($student, $course);
    }
}
