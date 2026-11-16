<?php

namespace App\Services\Learning;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Canonical read-side policy for student course and classroom access.
 *
 * Public pages, student workspaces, guardian dashboards and messaging must
 * use the same active-enrollment and classroom-membership rules.
 */
final class LearningAccessService
{
    public function activeCourseIds(User $student): Builder
    {
        $query = CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->currentlyActive()
            ->whereHas('course', fn (Builder $course) => $course->where('status', 'published'))
            ->select('course_id');

        return $student->is_active && $student->hasRole('student')
            ? $query
            : $query->whereRaw('1 = 0');
    }

    public function activeClassroomIds(User $student): Builder
    {
        $query = Classroom::query()
            ->where('classrooms.status', 'active')
            ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
            ->where(function (Builder $access) use ($student): void {
                $access->whereNull('classrooms.course_id')
                    ->orWhereHas('course', fn (Builder $course) => $course
                        ->where('status', 'published')
                        ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                            ->where('student_id', $student->id)
                            ->currentlyActive()));
            })
            ->select('classrooms.id');

        return $student->is_active && $student->hasRole('student')
            ? $query
            : $query->whereRaw('1 = 0');
    }

    public function hasCourseAccess(User $student, Course $course): bool
    {
        if (! $student->is_active || ! $student->hasRole('student') || $course->status !== 'published') {
            return false;
        }

        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->currentlyActive()
            ->exists();
    }
}
