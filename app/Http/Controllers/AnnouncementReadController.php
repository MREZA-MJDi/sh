<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AnnouncementReadController extends Controller
{
    public function store(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($request->user(), 403);

        $audiences = [
            'student' => ['all', 'students'],
            'parent' => ['all', 'parents'],
            'teacher' => ['all', 'teachers'],
            'admin' => ['all'],
        ];
        $role = $request->user()->roles()->value('name');
        abort_unless(in_array($announcement->audience, $audiences[$role] ?? [], true), 404);
        abort_unless(
            $announcement->status === 'published'
            && $announcement->published_at && $announcement->published_at->lte(now())
            && (! $announcement->starts_at || $announcement->starts_at->lte(now()))
            && (! $announcement->ends_at || $announcement->ends_at->gte(now())),
            404
        );

        $announcement->readers()->syncWithoutDetaching([
            $request->user()->id => ['read_at' => now()],
        ]);

        return back()->with('status', 'اعلان به‌عنوان خوانده‌شده ثبت شد.');
    }
}
