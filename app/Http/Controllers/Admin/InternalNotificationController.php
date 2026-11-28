<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInternalNotificationRequest;
use App\Jobs\SendAdminNotificationBroadcast;
use App\Models\AdminNotificationBroadcast;
use App\Models\User;
use App\Notifications\AdminPanelBroadcastNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class InternalNotificationController extends Controller
{
    public function index(): View
    {
        $audiences = [
            'all' => 'همه کاربران فعال',
            'students' => 'دانش‌آموزان',
            'parents' => 'اولیا',
            'teachers' => 'مدرسان',
            'admins' => 'مدیران',
        ];

        $counts = [
            'all' => User::query()->where('is_active', true)->count(),
            'students' => AdminNotificationBroadcast::recipients('students')->count(),
            'parents' => AdminNotificationBroadcast::recipients('parents')->count(),
            'teachers' => AdminNotificationBroadcast::recipients('teachers')->count(),
            'admins' => AdminNotificationBroadcast::recipients('admins')->count(),
        ];

        $broadcasts = AdminNotificationBroadcast::query()
            ->with('sender:id,name')
            ->latest()
            ->paginate(20);

        $broadcastIds = $broadcasts->getCollection()->pluck('id');
        $readCounts = DB::table('notifications')
            ->select('data->broadcast_id as broadcast_id')
            ->selectRaw('COUNT(*) as aggregate')
            ->where('type', AdminPanelBroadcastNotification::class)
            ->whereIn('data->broadcast_id', $broadcastIds)
            ->whereNotNull('read_at')
            ->groupBy('data->broadcast_id')
            ->pluck('aggregate', 'broadcast_id');

        $broadcasts->getCollection()->each(
            fn (AdminNotificationBroadcast $broadcast) => $broadcast->setAttribute(
                'read_count',
                (int) ($readCounts[$broadcast->id] ?? 0)
            )
        );

        return view('admin.internal-notifications.index', compact('audiences', 'counts', 'broadcasts'));
    }

    public function store(StoreInternalNotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $recipients = AdminNotificationBroadcast::recipients($data['audience']);
        $recipientCount = (clone $recipients)->count();

        if ($recipientCount === 0) {
            return back()->withInput()->withErrors([
                'audience' => 'برای این گروه، کاربر فعالی جهت دریافت اعلان پیدا نشد.',
            ]);
        }

        $broadcast = AdminNotificationBroadcast::create([
            'id' => (string) Str::uuid(),
            'sender_id' => $request->user()->id,
            'audience' => $data['audience'],
            'title' => $data['title'],
            'message' => $data['message'],
            'url' => $data['url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'recipient_count' => $recipientCount,
            'recipient_max_id' => (clone $recipients)->max('id'),
            'delivery_status' => 'queued',
        ]);

        try {
            SendAdminNotificationBroadcast::dispatch($broadcast->id)->afterCommit();
        } catch (\Throwable $exception) {
            report($exception);
            AdminNotificationBroadcast::whereKey($broadcast->id)
                ->where('delivery_status', 'queued')->update(['delivery_status' => 'failed']);

            return to_route('admin.internal-notifications.index')
                ->with('warning', 'قرار دادن اعلان در صف انجام نشد. وضعیت آن در تاریخچه ثبت شد.');
        }

        return to_route('admin.internal-notifications.index')
            ->with('success', 'اعلان ثبت شد. نتیجهٔ ارسال را در تاریخچهٔ اعلان‌ها ببینید.');
    }
}
