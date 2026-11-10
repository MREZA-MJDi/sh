<?php

namespace App\Services\Admin;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class MessageWorkspaceService
{
    public function queryFor(User $user): Builder
    {
        $query = Message::query()->whereNull('parent_id');

        if (! app(ClassroomWorkspaceService::class)->isTeacher($user)) {
            return $query;
        }

        $classroomIds = app(ClassroomWorkspaceService::class)
            ->queryFor($user)
            ->select('classrooms.id');

        return $query->where(function (Builder $scope) use ($classroomIds): void {
            $scope->whereHas(
                'sender.enrolledClasses',
                fn (Builder $classes) => $classes->whereIn('classrooms.id', $classroomIds)
            )->orWhereHas(
                'sender.children.enrolledClasses',
                fn (Builder $classes) => $classes->whereIn('classrooms.id', $classroomIds)
            );
        });
    }

    public function findVisible(User $user, int $messageId): Message
    {
        return $this->queryFor($user)
            ->whereKey($messageId)
            ->with(['sender', 'replies.sender'])
            ->firstOrFail();
    }
}
