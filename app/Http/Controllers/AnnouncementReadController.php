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

        $allowedAudiences = ['all'];
        foreach (['student' => 'students', 'parent' => 'parents', 'teacher' => 'teachers'] as $role => $audience) {
            if ($request->user()->hasRole($role)) {
                $allowedAudiences[] = $audience;
            }
        }
        abort_unless(in_array($announcement->audience, $allowedAudiences, true), 404);
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
