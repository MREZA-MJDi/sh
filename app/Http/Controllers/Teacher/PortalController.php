<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Homework;
use App\Models\MessageReply;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use App\Models\ParentMeeting;
use App\Services\Admin\AttendanceService;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\MessageWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PortalController extends Controller
{
    public function classes(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classrooms = $workspace->queryFor($request->user())
            ->where('status', 'active')
            ->with(['course:id,title,subject', 'spotPlayerVideos', 'learningResources', 'sessions' => fn ($query) => $query
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
            ->with(['classroom' => fn ($query) => $query->select('id', 'name')->withCount('students'), 'submissions' => fn ($query) => $query
                ->with(['student:id,name', 'homework:id,title'])
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

        $classroom = $classrooms->firstWhere('id', $request->integer('classroom_id')) ?? $classrooms->first();
        $classroom = $classroom
            ? $workspace->queryFor($request->user())->with('students:id,name')->findOrFail($classroom->id)
            : null;

        $sessions = $classroom
            ? ClassSession::query()->where('classroom_id', $classroom->id)
                ->where('status', '!=', 'cancelled')
                ->where('starts_at', '<=', now())
                ->latest('starts_at')->limit(12)->get()
            : collect();

        $session = $sessions->firstWhere('id', $request->integer('class_session_id')) ?? $sessions->first();
        $records = $session
            ? Attendance::query()->where('class_session_id', $session->id)->get()->keyBy('student_id')
            : collect();

        $students = $classroom?->students->map(fn ($student) => [
            'id' => $student->id,
            'name' => $student->name,
            'status' => $records->get($student->id)?->status,
            'note' => $records->get($student->id)?->note,
            'joined_at' => $records->get($student->id)?->joined_at?->format('Y-m-d\TH:i'),
            'attended_minutes' => $records->get($student->id)?->attended_minutes,
        ]) ?? collect();

        $history = Attendance::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->with(['student:id,name', 'classroom:id,name', 'session:id,starts_at'])
            ->latest('session_date')->latest('id')->limit(30)->get();

        return view('teacher.attendance', compact('classrooms', 'classroom', 'sessions', 'session', 'students', 'history'));
    }

    public function storeAttendance(
        StoreAttendanceRequest $request,
        AttendanceService $attendance,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $data = $request->validated();
        $classroom = $workspace->queryFor($request->user())->findOrFail($data['classroom_id']);
        $session = ClassSession::query()
            ->where('classroom_id', $classroom->id)
            ->findOrFail($data['class_session_id']);

        $attendance->record($classroom, $session, $data['attendance'], (int) $request->user()->id);

        return back()->with('success', 'حضور و غیاب جلسه ذخیره شد.');
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

        $selectedMessage = $request->filled('message')
            ? $messages->findVisible($request->user(), $request->integer('message'))
            : $conversations->getCollection()->first();

        if ($selectedMessage) {
            $selectedMessage->loadMissing(['sender:id,name', 'recipient:id,name', 'replies.sender:id,name']);
        }

        return view('teacher.messages', compact('conversations', 'selectedMessage'));
    }
    public function reply(
        ReplyMessageRequest $request,
        \App\Models\Message $message,
        MessageWorkspaceService $workspace
    ): RedirectResponse {
        $message = $workspace->findVisible($request->user(), $message->id);

        MessageReply::query()->create([
            'message_id' => $message->id,
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $message->update(['status' => 'answered', 'replied_at' => now()]);
        $otherParticipantId = (int) $message->sender_id === (int) $request->user()->id
            ? $message->recipient_id
            : $message->sender_id;
        User::query()->find($otherParticipantId)?->notify(
            new MessageActivityNotification($message, 'پاسخ جدیدی در گفت‌وگوی شما ثبت شد.')
        );

        return redirect()->route('teacher.messages.index', ['message' => $message->id])
            ->with('success', 'پاسخ شما ثبت شد.');
    }

    public function updateLiveSession(Request $request, int $session, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $data = $request->validate([
            'meeting_provider' => ['required', 'in:skyroom,bbb,link'],
            'meeting_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $record = ClassSession::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->findOrFail($session);

        $record->update([
            'meeting_provider' => $data['meeting_provider'],
            'meeting_url' => $data['meeting_url'] ?? null,
        ]);

        return back()->with('success', 'تنظیمات اتصال این جلسه ذخیره شد.');
    }

}
