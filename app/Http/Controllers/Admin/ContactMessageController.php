<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $messages = ContactMessage::query()
            ->with('handler:id,name')
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
