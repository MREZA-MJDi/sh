<?php
namespace App\Services\Admin;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
final class ExamWorkspaceService
{
    public function queryFor(User $user): Builder
    {
        return Exam::query()->when($user->hasRole('teacher') && ! $user->hasRole('admin'),
            fn (Builder $query) => $query->where(function (Builder $scope) use ($user): void {
                $scope->whereHas('classroom', fn (Builder $classes) => $classes->where('teacher_id', $user->id)
                    ->orWhereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id)))
                    ->orWhereHas('course', fn (Builder $course) => $course->where('teacher_id', $user->id));
            }));
    }
    public function owns(User $user, Exam $exam): bool
    {
        if ($user->hasRole('admin')) return true;
        $classAccess = $exam->classroom && ($exam->classroom->teacher_id === $user->id
            || $exam->classroom->course()->where('teacher_id', $user->id)->exists());
        return (bool) ($classAccess || $exam->course?->teacher_id === $user->id);
    }
}
