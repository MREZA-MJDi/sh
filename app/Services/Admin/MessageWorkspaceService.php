<?php

namespace App\Services\Admin;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
            ->where('classrooms.status', 'active')
            ->select('classrooms.id');

        $studentIds = DB::table('classroom_student')
            ->whereIn('classroom_id', $classroomIds)
            ->select('student_id');

        return $query
            ->where(function (Builder $scope) use ($studentIds, $user): void {
                $scope->whereIn('student_id', $studentIds)
                    ->orWhere(function (Builder $internal) use ($user): void {
                        $internal->whereNull('student_id')
                            ->where(function (Builder $participants) use ($user): void {
                                $participants->where('sender_id', $user->id)
                                    ->orWhere('recipient_id', $user->id);
                            });
                    });
            })
            ->where(function (Builder $participants) use ($user): void {
                $participants->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id);
            });
    }

    public function findVisible(User $user, int $messageId): Message
    {
        return $this->queryFor($user)
            ->whereKey($messageId)
            ->with(['sender', 'recipient', 'student', 'replies.sender'])
            ->firstOrFail();
    }
}
