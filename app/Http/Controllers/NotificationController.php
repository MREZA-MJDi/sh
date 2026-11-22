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
            $decodedTarget = rawurldecode($target);
            $isLocalPath = str_starts_with($target, '/')
                && ! str_starts_with($target, '//')
                && ! str_starts_with($decodedTarget, '//')
                && ! str_contains($decodedTarget, '\\\\')
                && ! preg_match('/[\\r\\n]/', $target);

            if ($isLocalAbsoluteUrl || $isLocalPath) {
                if ($request->user()->hasRole('teacher') && ! $request->user()->hasRole('admin')) {
                    $path = parse_url($target, PHP_URL_PATH) ?: '/';
                    $teacherTargets = [
                        '/admin/homework' => route('teacher.homework'),
                        '/admin/assignments' => route('teacher.homework'),
                        '/admin/resources' => route('teacher.resources'),
                        '/admin/spotplayer' => route('teacher.videos'),
                        '/admin/recordings' => route('teacher.videos'),
                        '/admin/meetings' => route('teacher.meetings'),
                        '/admin/parent-messages' => route('teacher.messages.index'),
                    ];
                    if (isset($teacherTargets[$path])) {
                        return redirect()->to($teacherTargets[$path]);
                    }
                    if ($path === '/admin' || $path === '/admin/') {
                        return redirect()->route('teacher.dashboard');
                    }
                    if (str_starts_with($path, '/admin/')) {
                        return redirect()->route('teacher.dashboard');
                    }
                }

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
