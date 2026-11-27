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
                        ->orWhere(fn (Builder $unassigned) => $unassigned->whereNull('teacher_id')
                            ->whereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id)));
                });
            });
    }

    public function coursesQueryFor(User $user): Builder
    {
        return Course::query()
            ->when($this->isTeacher($user), function (Builder $query) use ($user): void {
                $query->where(function (Builder $scope) use ($user): void {
                    $scope->where('teacher_id', $user->id)
                        ->orWhereHas('classrooms', fn (Builder $classrooms) => $classrooms->where('teacher_id', $user->id));
                });
            });
    }

    public function owns(User $user, Classroom $classroom): bool
    {
        return $this->queryFor($user)->whereKey($classroom->id)->exists();
    }

    public function ownsCourse(User $user, Course $course): bool
    {
        return ! $this->isTeacher($user)
            || $course->teacher_id === $user->id
            || $course->classrooms()->where('teacher_id', $user->id)->exists();
    }

    public function isTeacher(User $user): bool
    {
        return $user->hasRole('teacher') && ! $user->hasRole('admin');
    }
}
