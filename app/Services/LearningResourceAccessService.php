<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\CourseEnrollment;
use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class LearningResourceAccessService
{
    public function resolveStudent(User $actor, ?int $childId = null): User
    {
        if ($actor->hasRole('student')) {
            return $actor;
        }

        abort_unless($actor->hasRole('parent'), 404);

        return $actor->children()
            ->whereKey($childId)
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->firstOrFail();
    }

    public function authorizeDownload(User $student, LearningResource $resource): void
    {
        abort_unless(
            $resource->status === 'published'
                && (! $resource->release_at || $resource->release_at->lte(now()))
                && $resource->file_path
                && $resource->is_free,
            404
        );

        if ($resource->classroom_id) {
            $classroom = Classroom::query()
                ->whereKey($resource->classroom_id)
                ->where('status', 'active')
                ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
                ->firstOrFail();

            if ($resource->course_id) {
                abort_unless((int) $resource->course_id === (int) $classroom->course_id, 404);
            }

            $courseId = $resource->course_id ?: $classroom->course_id;
            if ($courseId) {
                abort_unless($this->hasActiveCourseAccess($student, (int) $courseId), 404);
            }

            return;
        }

        if ($resource->course_id) {
            abort_unless($this->hasActiveCourseAccess($student, (int) $resource->course_id), 404);
            return;
        }

        // Unscoped public/authenticated resources are available to signed-in
        // portal users. "Enrolled" resources always need a real class/course scope.
        abort_unless(in_array($resource->visibility, ['public', 'authenticated'], true), 404);
    }

    public function authorizePublicGuest(LearningResource $resource): void
    {
        abort_unless(
            $resource->status === 'published'
                && $resource->visibility === 'public'
                && ! $resource->classroom_id
                && ! $resource->course_id
                && (! $resource->release_at || $resource->release_at->lte(now()))
                && $resource->file_path
                && $resource->is_free
                && ($resource->downloadable || $resource->kind === 'pdf'),
            404
        );
    }

    private function hasActiveCourseAccess(User $student, int $courseId): bool
    {
        return CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->where(fn (Builder $expiry) => $expiry
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->whereHas('course', fn (Builder $course) => $course->where('status', 'published'))
            ->exists();
    }
}