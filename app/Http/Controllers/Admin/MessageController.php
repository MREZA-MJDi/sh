<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReply;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $messages = Message::with(['sender', 'replies.sender'])
            ->whereNull('parent_id')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()->paginate(15)->withQueryString();

        $selected = $request->filled('message')
            ? Message::with(['sender', 'replies.sender'])->findOrFail($request->integer('message'))
            : $messages->first();

        return view('admin.messages.index', compact('messages', 'selected'));
    }

    public function reply(Request $request, Message $message): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:10000']]);

        MessageReply::create([
            'message_id' => $message->id,
            'sender_id' => auth()->id(),
            'body' => $data['body'],
        ]);

        $message->update(['status' => 'answered', 'replied_at' => now()]);

        return redirect()->route('admin.messages.index', ['message' => $message->id])
            ->with('success', 'پاسخ در کارتابل پیام ثبت شد.');
    }

    public function markRead(Message $message): RedirectResponse
    {
        $message->update(['status' => 'read']);

        return back()->with('success', 'پیام به‌عنوان خوانده‌شده ثبت شد.');
    }
}
