<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Homework;
use App\Models\Message;
use App\Models\ParentMeeting;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\MessageWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PortalController extends Controller
{
    public function classes(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classrooms = $workspace->queryFor($request->user())
            ->where('status', 'active')
            ->with(['course:id,title,subject', 'sessions' => fn ($query) => $query
                ->where('starts_at', '>=', now())
                ->where('status', 'scheduled')
                ->orderBy('starts_at')
                ->limit(4)])
            ->withCount('students')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('teacher.classes', compact('classrooms'));
    }

    public function homework(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classroomIds = $workspace->queryFor($request->user())->select('classrooms.id');
        $homework = Homework::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->with(['classroom:id,name', 'submissions' => fn ($query) => $query
                ->with('student:id,name')
                ->latest('submitted_at')])
            ->withCount('submissions')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('teacher.homework', compact('homework'));
    }

    public function attendance(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classrooms = $workspace->queryFor($request->user())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $attendance = Attendance::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->with(['student:id,name', 'classroom:id,name', 'session:id,starts_at'])
            ->latest('session_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('teacher.attendance', compact('classrooms', 'attendance'));
    }

    public function live(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $sessions = ClassSession::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->where('status', 'scheduled')
            ->where('ends_at', '>=', now())
            ->with('classroom:id,name,meeting_url,skyroom_room_id')
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('teacher.live', compact('sessions'));
    }

    public function meetings(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $meetings = ParentMeeting::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->with(['guardian:id,name', 'student:id,name', 'classroom:id,name'])
            ->orderByRaw("CASE WHEN status IN ('requested', 'proposed') THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(starts_at, preferred_at) ASC')
            ->paginate(15)
            ->withQueryString();

        return view('teacher.meetings', compact('meetings'));
    }

    public function messages(Request $request, MessageWorkspaceService $messages): View
    {
        $conversations = $messages->queryFor($request->user())
            ->with(['sender:id,name', 'recipient:id,name', 'student:id,name', 'replies.sender:id,name'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('teacher.messages', compact('conversations'));
    }
}
