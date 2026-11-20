<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Models\Message;
use App\Models\Consultation;
use App\Models\MessageInternalNote;
use App\Models\MessageReply;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use App\Services\Admin\MessageWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request, MessageWorkspaceService $workspace): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['', 'open', 'answered', 'read', 'closed'])],
            'category' => ['nullable', Rule::in(['', 'parents', 'students', 'teachers', 'teacher_student'])],
            'sort' => ['nullable', Rule::in(['latest', 'oldest'])],
        ]);

        $base = $workspace->queryFor($request->user())
            ->with(['sender:id,name,email,phone,avatar_path', 'sender.roles:id,name',
                'recipient:id,name,email,phone,avatar_path', 'recipient.roles:id,name',
                'student:id,name', 'replies.sender:id,name', 'replies.sender.roles:id,name']);

        $term = trim((string) ($filters['q'] ?? ''));
        $messages = (clone $base)
            ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                $search->where('subject', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%")
                    ->orWhereHas('sender', fn ($sender) => $sender->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('recipient', fn ($recipient) => $recipient->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('replies', fn ($replies) => $replies->where('body', 'like', "%{$term}%"));
            }))
            ->when(($filters['status'] ?? '') === 'closed', fn ($query) => $query->whereNotNull('closed_at'))
            ->when(($filters['status'] ?? '') !== '' && ($filters['status'] ?? '') !== 'closed', fn ($query) => $query->whereNull('closed_at')->where('status', $filters['status']))
            ->when(($filters['category'] ?? '') === 'parents', fn ($query) => $query->where(fn ($q) => $q->whereHas('sender.roles', fn ($r) => $r->where('name', 'parent'))->orWhereHas('recipient.roles', fn ($r) => $r->where('name', 'parent'))))
            ->when(($filters['category'] ?? '') === 'students', fn ($query) => $query->where(fn ($q) => $q->whereHas('sender.roles', fn ($r) => $r->where('name', 'student'))->orWhereHas('recipient.roles', fn ($r) => $r->where('name', 'student'))))
            ->when(($filters['category'] ?? '') === 'teachers', fn ($query) => $query->where(fn ($q) => $q->whereHas('sender.roles', fn ($r) => $r->where('name', 'teacher'))->orWhereHas('recipient.roles', fn ($r) => $r->where('name', 'teacher'))))
            ->when(($filters['category'] ?? '') === 'teacher_student', fn ($query) => $query->where(fn ($q) => $q->where(fn ($pair) => $pair->whereHas('sender.roles', fn ($r) => $r->where('name', 'teacher'))->whereHas('recipient.roles', fn ($r) => $r->where('name', 'student')))->orWhere(fn ($pair) => $pair->whereHas('sender.roles', fn ($r) => $r->where('name', 'student'))->whereHas('recipient.roles', fn ($r) => $r->where('name', 'teacher')))))
            ->when(($filters['sort'] ?? 'latest') === 'oldest', fn ($query) => $query->oldest(), fn ($query) => $query->latest())
            ->paginate(40)->withQueryString();

        $selected = $request->filled('message')
            ? $workspace->findVisible($request->user(), $request->integer('message'))
            : $messages->first();

        if ($selected) {
            $selected->loadMissing(['sender.roles', 'recipient.roles', 'student', 'replies.sender.roles']);
        }

        $partner = $selected
            ? ((int) $selected->sender_id === (int) $request->user()->id ? $selected->recipient : $selected->sender)
            : null;
        $selectedRole = $partner?->roles?->first()?->name ?? 'user';

        $notes = $selected
            ? MessageInternalNote::query()->with('author:id,name')
                ->where('message_id', $selected->id)->latest()->get()
            : collect();

        $consultations = $selected
            ? Consultation::query()->with(['requester:id,name', 'student:id,name', 'teacher:id,name'])
                ->where(function ($query) use ($selected, $partner): void {
                    if ($selected->student_id) {
                        $query->where('student_id', $selected->student_id);
                    } elseif ($partner) {
                        $query->where('requester_id', $partner->id);
                    }
                })->latest()->limit(8)->get()
            : collect();

        return view('admin.messages.index', compact('messages', 'selected', 'partner', 'selectedRole', 'notes', 'consultations', 'filters'));
    }

    public function reply(
        ReplyMessageRequest $request,
        Message $message,
        MessageWorkspaceService $workspace
    ): RedirectResponse {
        $message = $workspace->findVisible($request->user(), $message->id);
        abort_if($message->closed_at !== null, 422, 'گفت‌وگوی بسته‌شده قابل پاسخ‌گویی نیست.');

        MessageReply::create([
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

        return redirect()->route('admin.messages.index', ['message' => $message->id])
            ->with('success', 'پاسخ در کارتابل پیام ثبت شد.');
    }

    public function markRead(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        $message = $workspace->findVisible($request->user(), $message->id);
        if ($message->closed_at === null) {
            $message->update(['status' => 'read']);
        }

        return back()->with('success', 'گفت‌وگو به‌عنوان خوانده‌شده ثبت شد.');
    }

    public function close(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        $message = $workspace->findVisible($request->user(), $message->id);
        $message->forceFill(['closed_at' => now()])->save();

        return back()->with('success', 'گفت‌وگو بسته شد.');
    }

    public function reopen(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        $message = $workspace->findVisible($request->user(), $message->id);
        $message->forceFill(['closed_at' => null])->save();

        return back()->with('success', 'گفت‌وگو دوباره باز شد.');
    }

    public function addNote(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        $message = $workspace->findVisible($request->user(), $message->id);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $partnerId = (int) $message->sender_id === (int) $request->user()->id
            ? $message->recipient_id : $message->sender_id;
        abort_unless($partnerId, 404);

        MessageInternalNote::create([
            'message_id' => $message->id,
            'user_id' => $partnerId,
            'created_by' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('success', 'یادداشت داخلی ذخیره شد.');
    }

    public function updateParticipant(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $message = $workspace->findVisible($request->user(), $message->id);
        $partnerId = (int) $message->sender_id === (int) $request->user()->id
            ? $message->recipient_id : $message->sender_id;
        $partner = User::query()->findOrFail($partnerId);
        abort_if($partner->hasRole(['admin', 'seo']) || ! $partner->hasRole(['student', 'parent', 'teacher']), 403);

        if ($request->has('phone')) {
            $digits = strtr((string) $request->input('phone'), [
                '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
                '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ]);
            $request->merge(['phone' => preg_replace('/[\\s-]+/u', '', trim($digits))]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($partner->id)],
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($partner->id)],
            'password' => ['nullable', 'string', 'min:12'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $partner->name = $data['name'];
        $partner->phone = $data['phone'] ?? null;
        $partner->email = $data['email'] ?? null;
        if (filled($data['password'] ?? null)) {
            $partner->password = $data['password'];
        }
        $oldAvatarPath = $partner->avatar_path;
        if ($request->hasFile('avatar')) {
            $partner->avatar_path = $request->file('avatar')->store('avatars/inbox', 'public');
        }
        $partner->save();
        if ($request->hasFile('avatar') && $oldAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return back()->with('success', 'اطلاعات کاربر به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $message = $workspace->findVisible($request->user(), $message->id);
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'گفت‌وگو و پاسخ‌های آن حذف شد.');
    }
}
