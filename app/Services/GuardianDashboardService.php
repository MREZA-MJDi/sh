<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Homework;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class GuardianDashboardService
{
    public function build(User $guardian): array
    {
        $children = $guardian->children()
            ->where('users.is_active', true)
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.grade']);

        $childIds = $children->modelKeys();

        $homeworks = Homework::query()
            ->where('status', 'published')
            ->whereHas('classroom.students', fn (Builder $query) => $query
                ->whereIn('users.id', $childIds))
            ->with('classroom:id,name')
            ->withCount('submissions')
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        $attendance = Attendance::query()
            ->whereIn('student_id', $childIds)
            ->with(['student:id,name', 'classroom:id,name'])
            ->latest('session_date')
            ->limit(6)
            ->get();

        $results = ExamResult::query()
            ->whereIn('student_id', $childIds)
            ->with(['student:id,name', 'exam:id,title'])
            ->latest()
            ->limit(6)
            ->get();

        $announcements = Announcement::query()
            ->where('status', 'published')
            ->whereIn('audience', ['all', 'parents'])
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'body', 'published_at']);

        return [
            'children' => $children,
            'homeworks' => $homeworks,
            'attendance' => $attendance,
            'results' => $results,
            'announcements' => $announcements,
        ];
    }
}
