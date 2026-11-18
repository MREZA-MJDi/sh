<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ClassSession;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\LearningResource;
use App\Models\SpotPlayerVideo;
use App\Models\MessageReply;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use App\Notifications\ParentMeetingNotification;
use App\Models\ParentMeeting;
use App\Services\Admin\AttendanceService;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\MessageWorkspaceService;
use App\Services\Skyroom\SkyroomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

final class PortalController extends Controller
{
    public function classes(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classrooms = $workspace->queryFor($request->user())
            ->where('status', 'active')
            ->with([
                'course:id,title,subject',
                'spotPlayerVideos' => fn ($query) => $query->latest()->limit(4),
                'learningResources' => fn ($query) => $query->latest()->limit(4),
                'sessions' => fn ($query) => $query
                    ->where('starts_at', '>=', now())
                    ->where('status', 'scheduled')
                    ->orderBy('starts_at')
                    ->limit(4),
            ])
            ->withCount('students')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('teacher.classes', compact('classrooms'));
    }

    public function homework(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classroomIds = $workspace->queryFor($request->user())->select('classrooms.id');
        $classrooms = $workspace->queryFor($request->user())
            ->where('classrooms.status', 'active')
            ->with('course:id,title')
            ->orderBy('classrooms.name')
            ->get(['classrooms.id', 'classrooms.name', 'classrooms.course_id']);
        $homework = Homework::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->with(['classroom' => fn ($query) => $query->select('id', 'name')->withCount('students')])
            ->withCount('submissions')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $submissions = HomeworkSubmission::query()
            ->where('status', 'submitted')
            ->whereHas('homework', fn ($query) => $query->whereIn('classroom_id', clone $classroomIds))
            ->with(['homework:id,title,classroom_id', 'student:id,name', 'files'])
            ->latest('submitted_at')
            ->paginate(15, ['*'], 'submissions')
            ->withQueryString();

        return view('teacher.homework', compact('homework', 'submissions', 'classrooms'));
    }

    public function videos(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $user = $request->user();
        $courseIds = $workspace->coursesQueryFor($user)->select('courses.id');
        $classroomIds = $workspace->queryFor($user)->select('classrooms.id');

        $videos = SpotPlayerVideo::query()
            ->with(['course:id,title', 'classroom:id,name'])
            ->where(function ($scope) use ($courseIds, $classroomIds): void {
                $scope->where(function ($classScope) use ($courseIds, $classroomIds): void {
                    $classScope->whereNotNull('classroom_id')
                        ->whereIn('classroom_id', clone $classroomIds)
                        ->whereIn('course_id', clone $courseIds);
                })->orWhere(function ($courseScope) use ($courseIds): void {
                    $courseScope->whereNull('classroom_id')->whereIn('course_id', clone $courseIds);
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $courses = $workspace->coursesQueryFor($user)
            ->where(function ($query): void {
                $query->where(function ($legacy): void {
                    $legacy->whereNull('learning_features')->where('delivery_type', 'spotplayer');
                })->orWhere('learning_features->recorded_video', true);
            })
            ->orderBy('title')
            ->get(['id', 'title']);

        $classrooms = $workspace->queryFor($user)
            ->whereIn('course_id', $courses->modelKeys())
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'course_id']);

        return view('teacher.videos', compact('videos', 'courses', 'classrooms'));
    }

    public function resources(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $user = $request->user();
        $classroomIds = $workspace->queryFor($user)->select('classrooms.id');
        $courseIds = $workspace->coursesQueryFor($user)->select('courses.id');

        $resources = LearningResource::query()
            ->with(['course:id,title', 'classroom:id,name'])
            ->where(function ($scope) use ($classroomIds, $courseIds): void {
                $scope->where(function ($classScope) use ($classroomIds, $courseIds): void {
                    $classScope->whereNotNull('classroom_id')
                        ->whereHas('classroom', function ($classrooms) use ($classroomIds): void {
                            $classrooms->whereIn('classrooms.id', clone $classroomIds)
                                ->where(function ($courseMatch): void {
                                    $courseMatch->whereNull('learning_resources.course_id')
                                        ->orWhereColumn('classrooms.course_id', 'learning_resources.course_id');
                                });
                        })
                        ->where(function ($courseScope) use ($courseIds): void {
                            $courseScope->whereNull('course_id')->orWhereIn('course_id', clone $courseIds);
                        });
                })->orWhere(function ($courseScope) use ($courseIds): void {
                    $courseScope->whereNull('classroom_id')->whereIn('course_id', clone $courseIds);
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $courses = $workspace->coursesQueryFor($user)->orderBy('title')->get(['id', 'title']);
        $classrooms = $workspace->queryFor($user)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'course_id']);

        return view('teacher.resources', compact('resources', 'courses', 'classrooms'));
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

    public function live(Request $request, ClassroomWorkspaceService $workspace, SkyroomService $skyroom): View
    {
        $sessions = ClassSession::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->where('session_type', 'online')
            ->where('status', 'scheduled')
            ->where('ends_at', '>=', now())
            ->with('classroom:id,name,meeting_url,skyroom_room_id,skyroom_status')
            ->orderBy('starts_at')
            ->paginate(12)
            ->withQueryString();

        $skyroomHealth = $skyroom->health();

        return view('teacher.live', compact('sessions', 'skyroomHealth'));
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

    public function updateMeeting(Request $request, int $meeting, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $record = ParentMeeting::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->findOrFail($meeting);

        $data = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'declined', 'cancelled', 'completed'])],
            'starts_at' => ['nullable', 'date', 'after:now', Rule::requiredIf(fn () => in_array($record->status, ['requested', 'proposed'], true) && $request->input('status') === 'scheduled')],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        if (in_array($record->status, ['requested', 'proposed'], true)) {
            abort_unless(in_array($data['status'], ['scheduled', 'declined', 'cancelled'], true), 422);
            if ($data['status'] === 'scheduled') {
                abort_unless(filled($data['starts_at'] ?? null), 422, 'برای تأیید جلسه باید زمان مشخص شود.');
            }
        } else {
            abort_unless($record->status === 'scheduled' && in_array($data['status'], ['completed', 'cancelled'], true), 422);
        }

        $record->update([
            'status' => $data['status'],
            'starts_at' => $data['starts_at'] ?? $record->starts_at ?? $record->preferred_at,
            'ends_at' => $data['ends_at'] ?? $record->ends_at,
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ]);

        $record->loadMissing(['guardian', 'student']);
        $message = $data['status'] === 'scheduled'
            ? 'درخواست جلسه شما توسط مدرس تأیید شد.'
            : ($data['status'] === 'declined' ? 'درخواست جلسه شما توسط مدرس رد شد.' : 'وضعیت جلسه شما به‌روزرسانی شد.');
        $record->guardian?->notify(new ParentMeetingNotification($record, $message));
        $record->student?->notify(new ParentMeetingNotification($record, 'وضعیت جلسه مرتبط با شما به‌روزرسانی شد.'));

        return redirect()->route('teacher.meetings')->with('success', 'وضعیت جلسه به‌روزرسانی شد.');
    }

    public function messages(Request $request, MessageWorkspaceService $messages): View
    {
        $conversations = $messages->queryFor($request->user())
            ->with(['sender:id,name', 'recipient:id,name', 'student:id,name'])
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
            'meeting_provider' => ['required', 'in:skyroom,jitsi,bbb,link'],
            'meeting_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $record = ClassSession::query()
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->where('session_type', 'online')
            ->findOrFail($session);

        $record->update([
            'meeting_provider' => $data['meeting_provider'],
            'meeting_url' => $data['meeting_url'] ?? null,
        ]);

        return back()->with('success', 'تنظیمات اتصال این جلسه ذخیره شد.');
    }

}
