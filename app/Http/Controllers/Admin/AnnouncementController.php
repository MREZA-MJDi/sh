<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementStatusRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
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

        Announcement::create($data + [
            'created_by' => auth()->id(),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return back()->with('success', 'اطلاعیه ذخیره شد.');
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
