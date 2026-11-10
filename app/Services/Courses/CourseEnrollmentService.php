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
     * A course enrollment is unique per student/course, not per classroom.
     * Reconcile the union of all classroom rosters and preserve verified payments.
     */
    public function syncCourseRoster(Course $course): void
    {
        $course->load('classrooms.students');

        $studentIds = $course->classrooms
            ->flatMap(fn (Classroom $classroom) => $classroom->students->modelKeys())
            ->unique()
            ->values();

        DB::transaction(function () use ($course, $studentIds): void {
            // Keep payment evidence, but suspend its access while a course is unpublished.
            CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('source', 'payment')
                ->update([
                    'status' => $course->status === 'published' ? 'active' : 'suspended',
                ]);

            foreach ($studentIds as $studentId) {
                $enrollment = CourseEnrollment::firstOrNew([
                    'course_id' => $course->id,
                    'student_id' => $studentId,
                ]);

                if ($enrollment->exists && $enrollment->source === 'payment') {
                    continue;
                }

                $status = match (true) {
                    $course->status !== 'published' => 'suspended',
                    $course->type === 'free' => 'active',
                    default => 'pending_payment',
                };

                $enrollment->fill([
                    'source' => 'classroom',
                    'status' => $status,
                    'granted_by' => auth()->id(),
                    'source_reference' => 'course:'.$course->id.':classroom-roster',
                    'granted_at' => $status === 'active' ? ($enrollment->granted_at ?? now()) : null,
                ])->save();
            }

            CourseEnrollment::query()
                ->where('course_id', $course->id)
                ->where('source', 'classroom')
                ->when(
                    $studentIds->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('student_id', $studentIds->all())
                )
                ->update([
                    'status' => 'revoked',
                    'granted_at' => null,
                ]);
        });
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

        return CourseEnrollment::updateOrCreate(
            ['course_id' => $course->id, 'student_id' => $student->id],
            [
                'source' => 'payment',
                'status' => 'active',
                'source_reference' => $paymentReference,
                'granted_at' => now(),
            ]
        );
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
