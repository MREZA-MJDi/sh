<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $selectedMessageId = $request->integer('message');
        $messages = ContactMessage::query()
            ->with('handler:id,name')
            ->when($selectedMessageId > 0, fn ($query) => $query->whereKey($selectedMessageId))
            ->when(in_array($status, ['new', 'in_progress', 'resolved', 'spam'], true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $counts = ContactMessage::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.contact-messages.index', compact('messages', 'counts', 'status'));
    }

    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validateWithBag('contactReply', ['reply' => ['required', 'string', 'max:5000']]);
        abort_unless($message->status !== 'spam' && filter_var($message->contact, FILTER_VALIDATE_EMAIL), 422);

        if (in_array(config('mail.default'), [null, 'log', 'array'], true)) {
            return back()->withInput()->withErrors([
                'reply' => 'سرویس ارسال ایمیل هنوز فعال نشده است.',
            ], 'contactReply');
        }

        try {
            Mail::raw($data['reply'], fn ($mail) => $mail->to($message->contact)
                ->subject('پاسخ پشتیبانی آکادمی شیخان · پیام '.$message->id));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'reply' => 'ارسال ایمیل انجام نشد. دوباره تلاش کنید.',
            ], 'contactReply');
        }

        $message->update([
            'status' => 'resolved',
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);

        return back()->with('success', 'پاسخ برای ارسال به ایمیل فرستنده ثبت شد.');
    }

    public function updateStatus(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['new', 'in_progress', 'resolved', 'spam'])],
        ]);

        $message->update([
            'status' => $data['status'],
            'handled_by' => $request->user()->id,
            'handled_at' => $data['status'] === 'new' ? null : now(),
        ]);

        return back()->with('success', 'وضعیت پیام تماس به‌روزرسانی شد.');
    }
    public function destroy(Request $request, ContactMessage $message): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $message->delete();

        return back()->with('success', 'پیام تماس حذف شد.');
    }

}
