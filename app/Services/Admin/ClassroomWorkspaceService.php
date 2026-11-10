<?php

namespace App\Services\Admin;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ClassroomWorkspaceService
{
    public function queryFor(User $user): Builder
    {
        return Classroom::query()
            ->when($this->isTeacher($user), function (Builder $query) use ($user): void {
                $query->where(function (Builder $scope) use ($user): void {
                    $scope->where('teacher_id', $user->id)
                        ->orWhereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id));
                });
            });
    }

    public function coursesQueryFor(User $user): Builder
    {
        return Course::query()
            ->when($this->isTeacher($user), fn (Builder $query) => $query->where('teacher_id', $user->id));
    }

    public function owns(User $user, Classroom $classroom): bool
    {
        return ! $this->isTeacher($user)
            || $classroom->teacher_id === $user->id
            || $classroom->course()->where('teacher_id', $user->id)->exists();
    }

    public function ownsCourse(User $user, Course $course): bool
    {
        return ! $this->isTeacher($user) || $course->teacher_id === $user->id;
    }

    public function isTeacher(User $user): bool
    {
        return $user->hasRole('teacher') && ! $user->hasRole('admin');
    }
}
