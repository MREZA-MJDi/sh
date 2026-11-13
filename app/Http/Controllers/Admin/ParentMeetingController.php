<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreParentMeetingRequest;
use App\Http\Requests\Admin\UpdateParentMeetingRequest;
use App\Models\Classroom;
use App\Models\ParentMeeting;
use App\Models\User;
use App\Services\Meetings\ParentMeetingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ParentMeetingController extends Controller
{
    public function index(Request $request): View
    {
        $meetings = ParentMeeting::query()->with(['guardian:id,name,phone', 'student:id,name,grade', 'classroom:id,name'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw('CASE WHEN scheduled_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('scheduled_at')->paginate(20)->withQueryString();

        $guardians = User::query()->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'parent'))
            ->with(['children' => fn ($q) => $q->where('is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', 'student'))->orderBy('name')])
            ->orderBy('name')->get(['id', 'name']);

        $classrooms = Classroom::query()->where('status', 'active')->with('students:id')->orderBy('name')->get(['id', 'name']);

        return view('admin.parent-meetings.index', compact('meetings', 'guardians', 'classrooms'));
    }

    public function store(StoreParentMeetingRequest $request, ParentMeetingService $service): RedirectResponse
    {
        $service->createByAdmin($request->user(), $request->validated());

        return back()->with('success', 'جلسه برای ولی انتخاب‌شده ثبت شد.');
    }

    public function update(UpdateParentMeetingRequest $request, ParentMeeting $meeting, ParentMeetingService $service): RedirectResponse
    {
        $service->updateByAdmin($meeting, $request->validated());

        return back()->with('success', 'وضعیت جلسه اولیا به‌روزرسانی شد.');
    }
}