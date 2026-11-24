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
    public function activeCourseIds(User $student, ?string $feature = null): Builder
    {
        $courseScope = function (Builder $course) use ($feature): void {
            $course->where('status', 'published');

            if ($feature === null) {
                return;
            }

            $course->where(function (Builder $featureScope) use ($feature): void {
                $featureScope->where('learning_features->'.$feature, true)
                    ->orWhere(function (Builder $legacy) use ($feature): void {
                        $legacy->where(fn (Builder $missing) => $missing
                            ->whereNull('learning_features')
                            ->orWhereNull('learning_features->'.$feature));

                        if ($feature === 'live_class') {
                            $legacy->where('delivery_type', 'skyroom');
                        } elseif ($feature === 'recorded_video') {
                            $legacy->where('delivery_type', 'spotplayer');
                        } elseif (! in_array($feature, ['resources', 'assignments', 'exams'], true)) {
                            $legacy->whereRaw('1 = 0');
                        }
                    });
            });
        };

        $query = Course::query()
            ->where($courseScope)
            ->where(fn (Builder $access) => $access->where('type', 'free')
                ->orWhereHas('enrollments', fn (Builder $enrolled) => $enrolled
                    ->where('student_id', $student->id)
                    ->currentlyActive()))
            ->select('courses.id as course_id');

        return $student->is_active && $student->hasRole('student')
            ? $query
            : $query->whereRaw('1 = 0');
    }

    public function activeClassroomIds(User $student, ?string $feature = null): Builder
    {
        $query = Classroom::query()
            ->where('classrooms.status', 'active')
            ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
            ->where(function (Builder $access) use ($student, $feature): void {
                $access->whereNull('classrooms.course_id')
                    ->orWhereHas('course', function (Builder $course) use ($student, $feature): void {
                        $course->where('status', 'published');
                        if ($feature !== null) {
                            $course->where(function (Builder $featureScope) use ($feature): void {
                        $featureScope->where('learning_features->'.$feature, true)
                            ->orWhere(function (Builder $legacy) use ($feature): void {
                                $legacy->where(fn (Builder $missing) => $missing
                                    ->whereNull('learning_features')
                                    ->orWhereNull('learning_features->'.$feature));

                                if ($feature === 'live_class') {
                                    $legacy->where('delivery_type', 'skyroom');
                                } elseif ($feature === 'recorded_video') {
                                    $legacy->where('delivery_type', 'spotplayer');
                                } elseif (! in_array($feature, ['resources', 'assignments', 'exams'], true)) {
                                    $legacy->whereRaw('1 = 0');
                                }
                            });
                    });
                        }
                        $course->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                            ->where('student_id', $student->id)
                            ->currentlyActive());
                    });
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

        if ($course->type === 'free') {
            return true;
        }

        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->currentlyActive()
            ->exists();
    }
}
