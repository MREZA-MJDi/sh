<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementStatusRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with('creator')->latest()->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $supportsHomepageTicket = Schema::hasColumn('announcements', 'display_as_ticket');
        $isHomepageAnnouncement = $supportsHomepageTicket && (bool) ($data['display_as_ticket'] ?? false);
        if (! $supportsHomepageTicket) {
            unset($data['display_as_ticket']);
        }

        DB::transaction(function () use ($data, $isHomepageAnnouncement): void {
            if ($isHomepageAnnouncement) {
                Announcement::query()->where('display_as_ticket', true)->update(['display_as_ticket' => false]);
                $data['audience'] = 'all';
                $data['starts_at'] = null;
                $data['ends_at'] = null;
            }

            $data['priority'] = $data['priority'] ?? 'normal';
            Announcement::query()->create($data + [
                'created_by' => auth()->id(),
                'published_at' => $data['status'] === 'published' ? now() : null,
            ]);
        });

        return back()->with('success', 'اطلاعیه ذخیره شد.');
    }

    public function update(StoreAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validated();
        $status = $data['status'];
        $supportsHomepageTicket = Schema::hasColumn('announcements', 'display_as_ticket');
        $isHomepageAnnouncement = $supportsHomepageTicket && (bool) ($data['display_as_ticket'] ?? false);
        if (! $supportsHomepageTicket) {
            unset($data['display_as_ticket']);
        }

        DB::transaction(function () use ($data, $status, $announcement, $isHomepageAnnouncement): void {
            if ($isHomepageAnnouncement) {
                Announcement::query()
                    ->where('display_as_ticket', true)
                    ->whereKeyNot($announcement->getKey())
                    ->update(['display_as_ticket' => false]);

                $data['audience'] = 'all';
                $data['starts_at'] = null;
                $data['ends_at'] = null;
            }

            $data['priority'] = $data['priority'] ?? $announcement->priority ?? 'normal';
            $announcement->update([
                ...$data,
                'published_at' => $status === 'published' ? ($announcement->published_at ?? now()) : null,
            ]);
        });

        return back()->with('success', 'اطلاعیه به‌روزرسانی شد.');
    }

    public function updateStatus(UpdateAnnouncementStatusRequest $request, Announcement $announcement): RedirectResponse
    {
        $status = $request->validated('status');

        $announcement->update([
            'status' => $status,
            'published_at' => $status === 'published' ? ($announcement->published_at ?? now()) : null,
        ]);

        return back()->with('success', 'وضعیت اطلاعیه تغییر کرد.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'اطلاعیه حذف شد.');
    }
}
