<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LearningResource;
use App\Models\User;
use App\Services\Learning\LearningAccessService;
use Illuminate\Database\Eloquent\Builder;

final class LearningResourceAccessService
{
    public function __construct(private readonly LearningAccessService $access) {}

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
                && ($resource->downloadable || $resource->kind === 'pdf'),
            404
        );

        $this->authorizePortalScope($student, $resource);

        $hasPaidAccess = $resource->is_free || \App\Models\ResourcePurchase::query()
            ->where('learning_resource_id', $resource->id)
            ->where('student_id', $student->id)
            ->where('status', 'paid')
            ->exists();

        // Do not reveal the existence of unpaid/private material to an unentitled user.
        abort_unless($hasPaidAccess, 404);
    }

    public function authorizePurchase(User $student, LearningResource $resource): void
    {
        abort_unless(
            $student->is_active
                && $student->hasRole('student')
                && $resource->status === 'published'
                && (! $resource->release_at || $resource->release_at->lte(now()))
                && ! $resource->is_free
                && (int) $resource->price > 0
                && filled($resource->file_path),
            404
        );

        $this->authorizePortalScope($student, $resource);
    }

    private function authorizePortalScope(User $student, LearningResource $resource): void
    {
        abort_unless($student->is_active && $student->hasRole('student'), 404);

        if ($resource->classroom_id) {
            $classroom = $this->access->activeClassroomIds($student, 'resources')
                ->whereKey($resource->classroom_id)
                ->addSelect('classrooms.course_id')->firstOrFail();

            abort_unless(! $resource->course_id || (int) $resource->course_id === (int) $classroom->course_id, 404);
            return;
        }

        if ($resource->course_id) {
            abort_unless($this->access->activeCourseIds($student, 'resources')->whereKey($resource->course_id)->exists(), 404);
            return;
        }

        // Unscoped public/authenticated resources can be used by signed-in portal users.
        // "Enrolled" visibility is never allowed without an actual course/class scope.
        abort_unless(in_array($resource->visibility, ['public', 'authenticated'], true), 404);
    }

    public function authorizePublicGuest(LearningResource $resource): void
    {
        abort_unless(
            $resource->status === 'published'
                && $resource->visibility === 'public'
                && ! $resource->classroom_id
                && (! $resource->release_at || $resource->release_at->lte(now()))
                && $resource->file_path
                && $resource->is_free
                && ($resource->downloadable || $resource->kind === 'pdf'),
            404
        );

        if ($resource->course_id) {
            $course = Course::query()->whereKey($resource->course_id)->where('status', 'published')->first();
            abort_unless($course && $course->featureEnabled('resources'), 404);
        }
    }
}
