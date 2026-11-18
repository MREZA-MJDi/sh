<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        $target = data_get($record->data, 'url');
        if (is_string($target) && $target !== '') {
            $targetHost = parse_url($target, PHP_URL_HOST);
            $currentHost = parse_url(url('/'), PHP_URL_HOST);
            $isLocalAbsoluteUrl = is_string($targetHost) && $targetHost === $currentHost;
            $isLocalPath = str_starts_with($target, '/') && ! str_starts_with($target, '//');

            if ($isLocalAbsoluteUrl || $isLocalPath) {
                return redirect()->to($target);
            }
        }

        return back()->with('success', 'اعلان خوانده شد.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'همه اعلان‌ها خوانده شدند.');
    }
}
