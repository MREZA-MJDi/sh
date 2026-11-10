<?php

namespace App\Services\Admin;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ExamWorkspaceService
{
    public function queryFor(User $user): Builder
    {
        $query = Exam::query();

        if ($user->hasRole('admin') || ! app(ClassroomWorkspaceService::class)->isTeacher($user)) {
            return $query;
        }

        $classroomIds = app(ClassroomWorkspaceService::class)
            ->queryFor($user)
            ->select('classrooms.id');
        $courseIds = app(ClassroomWorkspaceService::class)
            ->coursesQueryFor($user)
            ->select('courses.id');

        return $query->where(function (Builder $scope) use ($classroomIds, $courseIds): void {
            $scope->where(function (Builder $classScope) use ($classroomIds): void {
                $classScope->whereNotNull('classroom_id')
                    ->whereHas('classroom', function (Builder $classrooms) use ($classroomIds): void {
                        $classrooms->whereIn('classrooms.id', $classroomIds)
                            ->where(function (Builder $courseMatch): void {
                                $courseMatch->whereColumn('classrooms.course_id', 'exams.course_id')
                                    ->orWhere(function (Builder $bothUnscoped): void {
                                        $bothUnscoped->whereNull('classrooms.course_id')
                                            ->whereNull('exams.course_id');
                                    });
                            });
                    });
            })->orWhere(function (Builder $courseScope) use ($courseIds): void {
                $courseScope->whereNull('classroom_id')->whereIn('course_id', $courseIds);
            });
        });
    }

    public function owns(User $user, Exam $exam): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $this->queryFor($user)->whereKey($exam->id)->exists();
    }
}
