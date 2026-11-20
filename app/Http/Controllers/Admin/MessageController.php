<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Models\Message;
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
        ]);

        $base = $workspace->queryFor($request->user())
            ->with(['sender:id,name,email,phone,avatar_path', 'sender.roles:id,name',
                'recipient:id,name,email,phone,avatar_path', 'recipient.roles:id,name',
                'student:id,name', 'replies.sender:id,name']);

        $term = trim((string) ($filters['q'] ?? ''));
        $messages = (clone $base)
            ->when($term !== '', fn ($query) => $query->where(function ($search) use ($term): void {
                $search->where('subject', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%")
                    ->orWhereHas('sender', fn ($sender) => $sender->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('recipient', fn ($recipient) => $recipient->where('name', 'like', "%{$term}%"));
            }))
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', $filters['status']))
            ->latest()->paginate(40)->withQueryString();

        $selected = $request->filled('message')
            ? $workspace->findVisible($request->user(), $request->integer('message'))
            : $messages->first();

        if ($selected) {
            $selected->loadMissing(['sender.roles', 'recipient.roles', 'student', 'replies.sender']);
        }

        $partner = $selected
            ? ((int) $selected->sender_id === (int) $request->user()->id ? $selected->recipient : $selected->sender)
            : null;
        $selectedRole = $partner?->roles?->first()?->name ?? 'user';

        $notes = $selected
            ? MessageInternalNote::query()->with('author:id,name')
                ->where('message_id', $selected->id)->latest()->get()
            : collect();

        return view('admin.messages.index', compact('messages', 'selected', 'partner', 'selectedRole', 'notes', 'filters'));
    }

    public function reply(
        ReplyMessageRequest $request,
        Message $message,
        MessageWorkspaceService $workspace
    ): RedirectResponse {
        $message = $workspace->findVisible($request->user(), $message->id);
        abort_if($message->status === 'closed', 422, 'گفت‌وگوی بسته‌شده قابل پاسخ‌گویی نیست.');

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
        if ($message->status !== 'closed') {
            $message->update(['status' => 'read']);
        }

        return back()->with('success', 'گفت‌وگو به‌عنوان خوانده‌شده ثبت شد.');
    }

    public function close(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        $message = $workspace->findVisible($request->user(), $message->id);
        $message->update(['status' => 'closed']);

        return back()->with('success', 'گفت‌وگو بسته شد.');
    }

    public function reopen(Request $request, Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        $message = $workspace->findVisible($request->user(), $message->id);
        $message->update(['status' => 'open']);

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
        if ($request->hasFile('avatar')) {
            $newPath = $request->file('avatar')->store('avatars/inbox', 'public');
            if ($partner->avatar_path) {
                Storage::disk('public')->delete($partner->avatar_path);
            }
            $partner->avatar_path = $newPath;
        }
        $partner->save();

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
