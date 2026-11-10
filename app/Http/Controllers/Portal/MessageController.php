<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ReplyPortalMessageRequest;
use App\Http\Requests\Portal\StorePortalMessageRequest;
use App\Http\Requests\Portal\UpdatePortalMessageRequest;
use App\Models\Classroom;
use App\Models\Message;
use App\Models\MessageReply;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

final class MessageController extends Controller
{
    public function index(Request $request): View
    {
        [$student, $children, $selectedChild] = $this->context($request);

        $messages = Message::query()
            ->whereNull('parent_id')
            ->where('student_id', $student?->id ?? 0)
            ->when(
                $request->user()->hasRole('student'),
                fn (Builder $query) => $query->where(function (Builder $scope) use ($request): void {
                    $scope->where('sender_id', $request->user()->id)
                        ->orWhere('recipient_id', $request->user()->id);
                })
            )
            ->with(['sender:id,name', 'recipient:id,name', 'replies.sender:id,name'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('shared.messages.index', [
            'messages' => $messages,
            'children' => $children,
            'selected_child' => $selectedChild,
            'student' => $student,
        ]);
    }

    public function create(Request $request): View
    {
        [$student, $children, $selectedChild] = $this->context($request);

        abort_if(! $student, 403, 'برای ارسال پیام، ابتدا دانش‌آموزی به این حساب متصل کنید.');

        $recipients = $this->availableRecipients($student);

        return view('shared.messages.create', [
            'student' => $student,
            'children' => $children,
            'selected_child' => $selectedChild,
            'recipients' => $recipients,
        ]);
    }

    public function store(StorePortalMessageRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $student = $this->resolveStudent($request, (int) $data['student_id']);

        abort_unless(
            $this->availableRecipients($student)->contains('id', (int) $data['recipient_id']),
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

        return redirect()
            ->route($this->routeName($request, 'show'), $message)
            ->with('status', 'پیام شما با موفقیت ارسال شد.');
    }

    public function show(Request $request, Message $message): View
    {
        $message = $this->accessibleMessage($request, $message)
            ->load(['sender:id,name', 'recipient:id,name', 'replies.sender:id,name']);

        [$student, $children, $selectedChild] = $this->context($request);

        return view('shared.messages.show', [
            'message' => $message,
            'student' => $student,
            'children' => $children,
            'selected_child' => $selectedChild,
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

    private function availableRecipients(User $student): Collection
    {
        $classroomIds = Classroom::query()
            ->where('status', 'active')
            ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
            ->where(function (Builder $access) use ($student): void {
                $access->whereNull('classrooms.course_id')
                    ->orWhereHas('course', fn (Builder $course) => $course
                        ->where('status', 'published')
                        ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                            ->where('student_id', $student->id)
                            ->where('status', 'active')
                            ->where(fn (Builder $expiry) => $expiry
                                ->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now()))));
            })
            ->select('classrooms.id');

        $teacherIds = Classroom::query()
            ->whereIn('id', clone $classroomIds)
            ->whereNotNull('teacher_id')
            ->distinct()
            ->pluck('teacher_id');

        $adminIds = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'admin'))
            ->pluck('id');

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $teacherIds->merge($adminIds)->unique())
            ->orderBy('name')
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
            $actor->children()
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
