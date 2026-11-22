<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Http\\Requests\\Admin\\StoreInternalNotificationRequest;
use App\\Models\\User;
use App\\Notifications\\AdminPanelBroadcastNotification;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Support\\Collection;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Notification;
use Illuminate\\Support\\Str;
use Illuminate\\View\\View;

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
            'students' => $this->audienceQuery('students')->count(),
            'parents' => $this->audienceQuery('parents')->count(),
            'teachers' => $this->audienceQuery('teachers')->count(),
            'admins' => $this->audienceQuery('admins')->count(),
        ];

        $recentRows = DB::table('notifications')
            ->where('type', AdminPanelBroadcastNotification::class)
            ->latest()
            ->limit(500)
            ->get(['data', 'read_at', 'created_at']);

        $broadcasts = $recentRows
            ->map(function (object $row): array {
                return [
                    'data' => json_decode($row->data, true) ?: [],
                    'read_at' => $row->read_at,
                    'created_at' => $row->created_at,
                ];
            })
            ->filter(fn (array $row) => filled($row['data']['broadcast_id'] ?? null))
            ->groupBy(fn (array $row) => $row['data']['broadcast_id'])
            ->map(function (Collection $items): array {
                $first = $items->first();

                return [
                    'title' => $first['data']['title'] ?? 'اعلان درون‌پنلی',
                    'message' => $first['data']['message'] ?? '',
                    'audience' => $first['data']['audience'] ?? 'all',
                    'created_at' => $first['created_at'],
                    'recipients' => $items->count(),
                    'read_count' => $items->whereNotNull('read_at')->count(),
                ];
            })
            ->sortByDesc('created_at')
            ->take(20)
            ->values();

        return view('admin.internal-notifications.index', compact('audiences', 'counts', 'broadcasts'));
    }

    public function store(StoreInternalNotificationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $recipients = $this->audienceQuery($data['audience']);
        $recipientCount = (clone $recipients)->count();

        if ($recipientCount === 0) {
            return back()->withInput()->withErrors([
                'audience' => 'برای این گروه، کاربر فعالی جهت دریافت اعلان پیدا نشد.',
            ]);
        }

        $broadcastId = (string) Str::uuid();
        $notification = new AdminPanelBroadcastNotification(
            $data['title'],
            $data['message'],
            $data['url'] ?? null,
            $data['action_label'] ?? null,
            $broadcastId,
            $data['audience'],
            (int) $request->user()->id,
        );

        DB::transaction(function () use ($recipients, $notification): void {
            $recipients->chunkById(500, function (Collection $users) use ($notification): void {
                Notification::send($users, $notification);
            });
        });

        return to_route('admin.internal-notifications.index')
            ->with('success', 'اعلان درون‌پنلی برای '.number_format($recipientCount).' کاربر ارسال شد. این پیام در سایت عمومی منتشر نمی‌شود.');
    }

    private function audienceQuery(string $audience)
    {
        $role = match ($audience) {
            'students' => 'student',
            'parents' => 'parent',
            'teachers' => 'teacher',
            'admins' => 'admin',
            default => null,
        };

        return User::query()
            ->where('is_active', true)
            ->when($role, fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('name', $role)));
    }
}
