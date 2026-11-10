<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with('creator')->latest()->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
            'audience' => ['required', Rule::in(['all', 'students', 'parents', 'teachers'])],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        Announcement::create($data + [
            'created_by' => auth()->id(),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return back()->with('success', 'اطلاعیه ذخیره شد.');
    }

    public function updateStatus(Request $request, Announcement $announcement): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'archived'])]]);

        $announcement->update([
            'status' => $data['status'],
            'published_at' => $data['status'] === 'published' ? ($announcement->published_at ?? now()) : null,
        ]);

        return back()->with('success', 'وضعیت اطلاعیه تغییر کرد.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return back()->with('success', 'اطلاعیه حذف شد.');
    }
}
