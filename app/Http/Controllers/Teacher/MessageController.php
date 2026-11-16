<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MessageController extends Controller
{
    public function create(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classrooms = $workspace->queryFor($request->user())
            ->where('status', 'active')
            ->with(['course:id,title', 'students' => fn (Builder $students) => $students
                ->where('users.is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
                ->with(['guardians' => fn (Builder $guardians) => $guardians
                    ->where('users.is_active', true)
                    ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))
                    ->orderBy('name')])
                ->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('teacher.message-create', compact('classrooms'));
    }

    public function store(Request $request, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', 'string', 'regex:/^(admin|student:\d+:\d+|parent:\d+:\d+:\d+)$/'],
            'subject' => ['required', 'string', 'min:3', 'max:190'],
            'body' => ['required', 'string', 'min:5', 'max:30000'],
        ]);

        if ($data['target'] === 'admin') {
            $admins = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'admin'))
                ->orderBy('id')
                ->get();

            if ($admins->isEmpty()) {
                return back()->withInput()->withErrors(['target' => 'در حال حاضر مدیر فعالی برای دریافت پیام ثبت نشده است.']);
            }

            $message = Message::query()->create([
                'sender_id' => $request->user()->id,
                'recipient_id' => $admins->first()->id,
                'student_id' => null,
                'parent_id' => null,
                'subject' => trim($data['subject']),
                'body' => trim($data['body']),
                'status' => 'open',
            ]);

            foreach ($admins as $admin) {
                $admin->notify(new MessageActivityNotification($message, 'پیام جدیدی از طرف مدرس برای مدیریت آکادمی ارسال شده است.'));
            }

            return redirect()->route('teacher.messages.index')
                ->with('success', 'پیام برای کارتابل مدیریت آکادمی ارسال شد.');
        }

        $parts = explode(':', $data['target']);
        $type = $parts[0];
        $classroomId = (int) $parts[1];
        $studentId = (int) $parts[2];
        $recipientId = $type === 'parent' ? (int) ($parts[3] ?? 0) : $studentId;

        $classroom = $workspace->queryFor($request->user())
            ->where('status', 'active')
            ->findOrFail($classroomId);

        $student = $classroom->students()
            ->whereKey($studentId)
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->firstOrFail();

        $recipient = $student;
        if ($type === 'parent') {
            $recipient = $student->guardians()
                ->whereKey($recipientId)
                ->where('users.is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))
                ->firstOrFail();
        }

        $message = Message::query()->create([
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipient->id,
            'student_id' => $student->id,
            'parent_id' => null,
            'subject' => trim($data['subject']),
            'body' => trim($data['body']),
            'status' => 'open',
        ]);

        $recipient->notify(new MessageActivityNotification($message, 'پیام جدیدی از طرف مدرس برای شما ارسال شده است.'));

        return redirect()->route('teacher.messages.index')
            ->with('success', 'پیام برای مخاطب مرتبط ارسال شد.');
    }
}