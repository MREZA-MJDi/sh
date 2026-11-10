<?php

namespace App\Services\Admin;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ClassroomWorkspaceService
{
    public function queryFor(User $user): Builder
    {
        return Classroom::query()
            ->when(
                $user->hasRole('teacher') && ! $user->hasRole('admin'),
                fn (Builder $query) => $query->where('teacher_id', $user->id)
            );
    }

    public function owns(User $user, Classroom $classroom): bool
    {
        return $user->hasRole('admin') || $classroom->teacher_id === $user->id;
    }
}
