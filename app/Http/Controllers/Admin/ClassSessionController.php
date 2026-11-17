<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveClassSessionRequest;
use App\Models\ClassSession;
use App\Models\Classroom;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClassSessionController extends Controller
{
    public function index(Request $request, int $classroom, ClassroomWorkspaceService $workspace): View
    {
        $classroom = $workspace->queryFor($request->user())->with(['course:id,title', 'teacher:id,name'])->findOrFail($classroom);

        $sessions = ClassSession::query()
            ->where('classroom_id', $classroom->id)
            ->withCount('attendances')
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString();

        $nextSession = ClassSession::query()
            ->where('classroom_id', $classroom->id)
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        return view('admin.classes.sessions', compact('classroom', 'sessions', 'nextSession'));
    }

    public function store(SaveClassSessionRequest $request, int $classroom, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $classroom = $workspace->queryFor($request->user())->findOrFail($classroom);
        $data = $request->validated();
        $provider = $data['meeting_provider'] ?? 'skyroom';
        $meetingUrl = $data['session_type'] === 'online'
            ? (filled($data['meeting_url'] ?? null) ? $data['meeting_url'] : ($provider === 'skyroom' ? $classroom->meeting_url : null))
            : null;
        ClassSession::create([
            ...$data,
            // اگر لینک جلسه خالی بود، لینک اسکای‌روم خود کلاس استفاده می‌شود.
            'meeting_url' => $meetingUrl,
            'meeting_provider' => $data['session_type'] === 'online' ? ($data['meeting_provider'] ?? 'skyroom') : null,
            'classroom_id' => $classroom->id,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'جلسه جدید ثبت شد.');
    }

    public function update(SaveClassSessionRequest $request, int $classSession, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $session = $this->ownedSession($request, $classSession, $workspace);
        $data = $request->validated();
        $provider = $data['meeting_provider'] ?? $session->meeting_provider ?? 'skyroom';
        $meetingUrl = $data['session_type'] === 'online'
            ? (filled($data['meeting_url'] ?? null) ? $data['meeting_url'] : ($provider === 'skyroom' ? $session->classroom?->meeting_url : null))
            : null;
        $session->update([
            ...$data,
            'meeting_url' => $meetingUrl,
            'meeting_provider' => $data['session_type'] === 'online' ? $provider : null,
        ]);

        return back()->with('success', 'جلسه به‌روزرسانی شد.');
    }

    public function destroy(Request $request, int $classSession, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyPermission(['classes.manage', 'classes.manage.assigned']), 403);

        $session = $this->ownedSession($request, $classSession, $workspace);

        if ($session->attendances()->exists() || DB::table('class_session_join_logs')->where('class_session_id', $session->id)->exists()) {
            return back()->withErrors(['session' => 'برای این جلسه حضور یا کلیک ورود ثبت شده و حذف نمی‌شود. به‌جای حذف، آن را «لغو شده» کنید.']);
        }

        $session->delete();

        return back()->with('success', 'جلسه حذف شد.');
    }

    private function ownedSession(Request $request, int $id, ClassroomWorkspaceService $workspace): ClassSession
    {
        $session = ClassSession::query()->findOrFail($id);

        // مدرس فقط جلسه‌های کلاس‌های خودش را می‌بیند؛ در غیر این صورت 404.
        abort_unless($workspace->queryFor($request->user())->whereKey($session->classroom_id)->exists(), 404);

        return $session;
    }
}