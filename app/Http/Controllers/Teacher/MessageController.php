<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class MessageController extends Controller
{
    public function create(): View
    {
        return view('teacher.message-create');
    }

    public function recipients(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $term = trim($data['q']);
        $classroomIds = $workspace->queryFor($request->user())
            ->where('classrooms.status', 'active')
            ->select('classrooms.id');

        $students = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->whereHas('enrolledClasses', fn (Builder $classes) => $classes
                ->whereIn('classrooms.id', clone $classroomIds))
            ->where(fn (Builder $search) => $search
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            ->with([
                'enrolledClasses' => fn ($classes) => $classes
                    ->whereIn('classrooms.id', clone $classroomIds)
                    ->where('classrooms.status', 'active')
                    ->orderBy('classrooms.name')
                    ->select('classrooms.id', 'classrooms.name'),
                'guardians' => fn ($guardians) => $guardians
                    ->where('users.is_active', true)
                    ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))
                    ->orderBy('users.name')
                    ->select('users.id', 'users.name'),
            ])
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        $items = [];
        foreach ($students as $student) {
            foreach ($student->enrolledClasses as $classroom) {
                $items[] = [
                    'target' => "student:{$classroom->id}:{$student->id}",
                    'label' => "دانش‌آموز: {$student->name}",
                    'meta' => $classroom->name,
                ];

                foreach ($student->guardians as $guardian) {
                    $items[] = [
                        'target' => "parent:{$classroom->id}:{$student->id}:{$guardian->id}",
                        'label' => "ولی {$student->name}: {$guardian->name}",
                        'meta' => $classroom->name,
                    ];
                }
            }
        }

        return response()->json(['data' => array_slice($items, 0, 40)]);
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
                ->orderBy('id');

            $firstAdmin = (clone $admins)->first();

            if (! $firstAdmin) {
                return back()->withInput()->withErrors(['target' => 'در حال حاضر مدیر فعالی برای دریافت پیام ثبت نشده است.']);
            }

            $message = Message::query()->create([
                'sender_id' => $request->user()->id,
                'recipient_id' => $firstAdmin->id,
                'student_id' => null,
                'parent_id' => null,
                'subject' => trim($data['subject']),
                'body' => trim($data['body']),
                'status' => 'open',
            ]);

            $admins->chunkById(100, fn ($recipients) => $recipients->each(
                fn (User $admin) => $admin->notify(new MessageActivityNotification(
                    $message,
                    'پیام جدیدی از طرف مدرس برای مدیریت آکادمی ارسال شده است.'
                ))
            ));

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