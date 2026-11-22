<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOnlineClassIndexRequest;
use App\Models\ClassSession;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\View\View;

final class OnlineClassController extends Controller
{
    public function index(
        AdminOnlineClassIndexRequest $request,
        ClassroomWorkspaceService $workspace
    ): View {
        $user = $request->user();
        $filters = $request->validated();
        $visibleClassroomIds = $workspace->queryFor($user)->select('classrooms.id');

        $visibleSessionsQuery = ClassSession::query()
            ->where('session_type', 'online')
            ->whereIn('classroom_id', $visibleClassroomIds)
            ->with(['classroom.course:id,title', 'classroom.teacher:id,name'])
            ->withCount('attendances');

        $stats = [
            'today' => (clone $visibleSessionsQuery)->whereDate('starts_at', today())->count(),
            'week' => (clone $visibleSessionsQuery)
                ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'upcoming' => (clone $visibleSessionsQuery)
                ->where('status', 'scheduled')
                ->where('starts_at', '>=', now())
                ->count(),
            'waiting' => $workspace->queryFor($user)
                ->whereNull('meeting_url')
                ->whereNull('skyroom_room_id')
                ->whereHas('sessions', fn ($sessions) => $sessions
                    ->where('session_type', 'online')
                    ->whereNull('meeting_url'))
                ->count(),
        ];

        $sessionsQuery = clone $visibleSessionsQuery;
        $sessionsQuery
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters): void {
                $term = trim($filters['q']);
                $query->where(function ($search) use ($term): void {
                    $search->where('title', 'like', "%{$term}%")
                        ->orWhereHas('classroom', fn ($classrooms) => $classrooms
                            ->where('name', 'like', "%{$term}%")
                            ->orWhereHas('course', fn ($courses) => $courses->where('title', 'like', "%{$term}%"))
                            ->orWhereHas('teacher', fn ($teachers) => $teachers->where('name', 'like', "%{$term}%")));
                });
            });

        $sessions = $sessionsQuery
            ->orderByRaw('CASE WHEN starts_at >= ? THEN 0 ELSE 1 END', [now()])
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('admin.online-classes.index', compact('sessions', 'stats', 'filters'));
    }
}
