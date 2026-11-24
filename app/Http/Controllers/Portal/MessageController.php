<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReplyPortalMessageRequest;
use App\Http\Requests\Portal\StorePortalMessageRequest;
use App\Http\Requests\Portal\UpdatePortalMessageRequest;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use App\Services\Learning\LearningAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

final class MessageController extends Controller
{
    public function index(Request $request): View
    {
        [$student, $children, $selectedChild] = $this->context($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['open', 'answered', 'closed', 'deleted'])],
            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'subject'])],
        ]);

        $messages = Message::query()
            ->whereNull('parent_id')
            ->where('student_id', $student?->id ?? 0)
            ->when(filled($filters['q'] ?? null), fn (Builder $query) => $query->where(function (Builder $search) use ($filters): void {
                $search->where('subject', 'like', '%'.$filters['q'].'%')
                    ->orWhere('body', 'like', '%'.$filters['q'].'%');
            }))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->where(function (Builder $participants) use ($request): void {
                $participants->where('sender_id', $request->user()->id)
                    ->orWhere('recipient_id', $request->user()->id);
            })
            ->with(['sender:id,name', 'recipient:id,name'])
            ->withCount('replies')
            ->when(($filters['sort'] ?? 'newest') === 'oldest', fn (Builder $query) => $query->oldest()->orderBy('id'))
            ->when(($filters['sort'] ?? 'newest') === 'subject', fn (Builder $query) => $query->orderBy('subject')->orderByDesc('id'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn (Builder $query) => $query->latest()->orderByDesc('id'))
            ->paginate(12)
            ->withQueryString();

        $selectedMessage = $request->filled('message')
            ? $this->accessibleMessage($request, Message::query()->findOrFail($request->integer('message')))
            : $messages->getCollection()->first();

        if ($selectedMessage) {
            $selectedMessage->loadMissing(['sender:id,name', 'recipient:id,name']);
            $selectedMessage->loadCount('replies');
            $selectedMessage->setRelation(
                'replies',
                $selectedMessage->replies()->with('sender:id,name')->latest()->limit(50)->get()->reverse()->values()
            );
        }

        return view($request->user()->hasRole('parent') ? 'parent.messages' : 'student.messages', [
            'messages' => $messages,
            'selectedMessage' => $selectedMessage,
            'children' => $children,
            'selected_child' => $selectedChild,
            'kids' => $children,
            'kid' => $selectedChild,
            'student' => $student,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, LearningAccessService $access): View
    {
        [$student, $children, $selectedChild] = $this->context($request);

        abort_if(! $student, 403, 'برای ارسال پیام، ابتدا دانش‌آموزی به این حساب متصل کنید.');

        $recipients = $this->availableRecipients($student, $access);

        return view($request->user()->hasRole('parent') ? 'parent.message-create' : 'student.message-create', [
            'student' => $student,
            'children' => $children,
            'selected_child' => $selectedChild,
            'kids' => $children,
            'kid' => $selectedChild,
            'recipients' => $recipients,
        ]);
    }

    public function store(StorePortalMessageRequest $request, LearningAccessService $access): RedirectResponse
    {
        $data = $request->validated();
        $student = $this->resolveStudent($request, (int) $data['student_id']);

        abort_unless(
            $this->availableRecipients($student, $access)->contains('id', (int) $data['recipient_id']),
            403,
            'گیرنده‌ی انتخاب‌شده برای این دانش‌آموز مجاز نیست.'
        );

        $message = Message::query()->create([
            'sender_id' => $request->user()->id,
            'recipient_id' => (int) $data['recipient_id'],
            'student_id' => $student->id,
            'parent_id' => null,
            'subject' => $data['subject'],
            'body' => $data['body'],
            'status' => 'open',
        ]);

        User::query()->find($message->recipient_id)?->notify(new MessageActivityNotification($message, 'پیام جدیدی برای شما ارسال شده است.'));

        return redirect()
            ->route($this->routeName($request, 'show'), $message)
            ->with('status', 'پیام شما با موفقیت ارسال شد.');
    }

    public function show(Request $request, Message $message): View
    {
        $message = $this->accessibleMessage($request, $message)
            ->load(['sender:id,name', 'recipient:id,name'])
            ->loadCount('replies');
        $message->setRelation(
            'replies',
            $message->replies()->with('sender:id,name')->latest()->limit(50)->get()->reverse()->values()
        );

        if ($request->user()->hasRole('parent')) {
            $request->merge(['child' => $message->student_id]);
        }

        [$student, $children, $selectedChild] = $this->context($request);

        return view($request->user()->hasRole('parent') ? 'parent.message-show' : 'student.message-show', [
            'message' => $message,
            'student' => $student,
            'children' => $children,
            'selected_child' => $selectedChild,
            'kids' => $children,
            'kid' => $selectedChild,
        ]);
    }

    public function reply(ReplyPortalMessageRequest $request, Message $message): RedirectResponse
    {
        $message = $this->accessibleMessage($request, $message);

        abort_if(in_array($message->status, ['closed', 'deleted'], true), 422, 'این گفتگو بسته شده است.');

        MessageReply::query()->create([
            'message_id' => $message->id,
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $message->update(['replied_at' => now()]);
        $otherParticipantId = (int) $message->sender_id === (int) $request->user()->id
            ? $message->recipient_id
            : $message->sender_id;
        User::query()->find($otherParticipantId)?->notify(new MessageActivityNotification($message, 'پاسخ جدیدی در گفت‌وگوی شما ثبت شد.'));

        return back()->with('status', 'پاسخ شما در گفتگو ثبت شد.');
    }

    public function update(UpdatePortalMessageRequest $request, Message $message): RedirectResponse
    {
        $message = $this->accessibleMessage($request, $message);

        abort_unless($message->sender_id === $request->user()->id, 403);
        abort_if($message->status !== 'open' || $message->replies()->exists(), 422, 'پس از پاسخ‌گرفتن، ویرایش پیام ممکن نیست.');

        $message->update($request->validated());

        return back()->with('status', 'پیام به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Message $message): RedirectResponse
    {
        $message = $this->accessibleMessage($request, $message);

        abort_unless($message->sender_id === $request->user()->id, 403);
        abort_if($message->status !== 'open' || $message->replies()->exists(), 422, 'این پیام دیگر قابل حذف نیست.');

        $message->delete();

        return redirect()->route($this->routeName($request, 'index'))->with('status', 'پیام حذف شد.');
    }

    /**
     * @return array{0: User|null, 1: Collection<int, User>, 2: User|null}
     */
    private function context(Request $request): array
    {
        $actor = $request->user();

        if ($actor->hasRole('student')) {
            return [$actor, collect(), $actor];
        }

        $children = $actor->children()
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.grade']);

        $selectedChild = $children->firstWhere('id', $request->integer('child')) ?? $children->first();

        return [$selectedChild, $children, $selectedChild];
    }

    private function resolveStudent(Request $request, int $studentId): User
    {
        $actor = $request->user();

        if ($actor->hasRole('student')) {
            abort_unless($actor->id === $studentId, 403);

            return $actor;
        }

        return $actor->children()
            ->whereKey($studentId)
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->firstOrFail();
    }

    private function availableRecipients(User $student, LearningAccessService $access): Collection
    {
        $classroomIds = $access->activeClassroomIds($student);
        $visibleClassrooms = Classroom::query()
            ->whereIn('id', clone $classroomIds)
            ->get(['id', 'course_id', 'teacher_id']);

        $courseIds = $visibleClassrooms->pluck('course_id')->filter()->unique();
        $teacherIds = $visibleClassrooms->pluck('teacher_id')
            ->filter()
            ->merge(
                Course::query()
                    ->whereIn('id', $courseIds)
                    ->whereNotNull('teacher_id')
                    ->pluck('teacher_id')
            )
            ->unique()
            ->values();

        return User::query()
            ->where('is_active', true)
            ->where(function (Builder $recipients) use ($teacherIds): void {
                $recipients->where(function (Builder $teachers) use ($teacherIds): void {
                    $teachers->whereIn('users.id', $teacherIds)
                        ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'teacher'));
                })->orWhereHas('roles', fn (Builder $roles) => $roles->where('name', 'admin'));
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    private function accessibleMessage(Request $request, Message $message): Message
    {
        $message = Message::query()->whereNull('parent_id')->whereKey($message->getKey())->firstOrFail();
        $actor = $request->user();

        if ($actor->hasRole('student')) {
            abort_unless(
                (int) $message->student_id === (int) $actor->id
                    && ((int) $message->sender_id === (int) $actor->id || (int) $message->recipient_id === (int) $actor->id),
                404
            );

            return $message;
        }

        abort_unless(
            $actor->hasRole('parent')
                && (int) $message->student_id > 0
                && ((int) $message->sender_id === (int) $actor->id || (int) $message->recipient_id === (int) $actor->id)
                && $actor->children()
                    ->whereKey($message->student_id)
                    ->where('users.is_active', true)
                    ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
                    ->exists(),
            404
        );

        return $message;
    }

    private function routeName(Request $request, string $action): string
    {
        return $request->user()->hasRole('parent')
            ? 'guardian.messages.'.$action
            : 'student.messages.'.$action;
    }
}
