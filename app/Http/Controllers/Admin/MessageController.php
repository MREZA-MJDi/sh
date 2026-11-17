<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReplyMessageRequest;
use App\Models\MessageReply;
use App\Models\User;
use App\Notifications\MessageActivityNotification;
use App\Services\Admin\MessageWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request, MessageWorkspaceService $workspace): View
    {
        $messages = $workspace->queryFor($request->user())
            ->with(['sender', 'recipient', 'student', 'replies.sender'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        $selected = $request->filled('message')
            ? $workspace->findVisible($request->user(), $request->integer('message'))
            : $messages->first();

        return view('admin.messages.index', compact('messages', 'selected'));
    }

    public function reply(
        ReplyMessageRequest $request,
        \App\Models\Message $message,
        MessageWorkspaceService $workspace
    ): RedirectResponse {
        $message = $workspace->findVisible($request->user(), $message->id);

        MessageReply::create([
            'message_id' => $message->id,
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $message->update(['status' => 'answered', 'replied_at' => now()]);
        $otherParticipantId = (int) $message->sender_id === (int) $request->user()->id
            ? $message->recipient_id
            : $message->sender_id;
        User::query()->find($otherParticipantId)?->notify(new MessageActivityNotification($message, 'پاسخ جدیدی در گفت‌وگوی شما ثبت شد.'));

        return redirect()->route('admin.messages.index', ['message' => $message->id])
            ->with('success', 'پاسخ در کارتابل پیام ثبت شد.');
    }

    public function markRead(
        Request $request,
        \App\Models\Message $message,
        MessageWorkspaceService $workspace
    ): RedirectResponse {
        $message = $workspace->findVisible($request->user(), $message->id);
        $message->update(['status' => 'read']);

        return back()->with('success', 'پیام به‌عنوان خوانده‌شده ثبت شد.');
    }
    public function destroy(Request $request, \App\Models\Message $message, MessageWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $message = $workspace->findVisible($request->user(), $message->id);
        $message->delete();

        return redirect()->route('admin.messages.index')->with('success', 'گفت‌وگو و پاسخ‌های آن حذف شد.');
    }

}
