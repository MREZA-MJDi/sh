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
    /**
     * Membership in a classroom grants course access only for free courses.
     * A paid course remains pending_payment until a verified payment callback.
     */
    public function syncClassroomRoster(Classroom $classroom): void
    {
        $classroom->loadMissing(['course', 'students']);

        if (! $classroom->course) {
            return;
        }

        $course = $classroom->course;

        DB::transaction(function () use ($classroom, $course): void {
            foreach ($classroom->students as $student) {
                $enrollment = CourseEnrollment::firstOrNew([
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                ]);

                // Never downgrade a verified paid enrollment because a classroom roster was refreshed.
                if ($course->type === 'paid' && $enrollment->exists && $enrollment->status === 'active') {
                    continue;
                }

                $isFreeAndPublished = $course->type === 'free' && $course->status === 'published';

                $enrollment->fill([
                    'source' => $enrollment->source === 'payment' ? 'payment' : 'classroom',
                    'status' => $isFreeAndPublished
                        ? 'active'
                        : ($course->type === 'paid' ? 'pending_payment' : 'pending_publication'),
                    'granted_by' => $enrollment->source === 'payment' ? $enrollment->granted_by : auth()->id(),
                    'source_reference' => 'classroom:'.$classroom->id,
                    'granted_at' => $isFreeAndPublished ? ($enrollment->granted_at ?? now()) : null,
                ])->save();
            }
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
        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
