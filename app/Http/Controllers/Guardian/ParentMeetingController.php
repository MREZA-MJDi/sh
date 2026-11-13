<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\StoreParentMeetingRequest;
use App\Models\Classroom;
use App\Models\ParentMeeting;
use App\Services\Meetings\ParentMeetingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ParentMeetingController extends Controller
{
    public function index(Request $request, ParentMeetingService $service): View
    {
        $children = $request->user()->children()->where('users.is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'student'))
            ->orderBy('users.name')->get(['users.id', 'users.name', 'users.grade']);

        $classrooms = Classroom::query()->where('status', 'active')->whereHas('students', fn ($q) => $q->whereIn('users.id', $children->pluck('id')))->with('students:id')->orderBy('name')->get(['id', 'name']);

        $meetings = $service->visibleToGuardian($request->user())
            ->with(['student:id,name,grade', 'classroom:id,name'])
            ->latest('created_at')->paginate(12)->withQueryString();

        return view('parent.meetings.index', compact('children', 'meetings', 'classrooms'));
    }

    public function store(StoreParentMeetingRequest $request, ParentMeetingService $service): RedirectResponse
    {
        $data = $request->validated();
        $meeting = $service->createForGuardian($request->user(), $data);

        return redirect()->route('guardian.meetings.index')->with('status', 'درخواست جلسه ثبت شد.');
    }

    public function respond(Request $request, ParentMeeting $meeting, ParentMeetingService $service): RedirectResponse
    {
        $data = $request->validate(['response' => ['required', 'in:accepted,declined']]);
        $service->respondAsGuardian($request->user(), $meeting, $data['response']);

        return back()->with('status', 'پاسخ شما ثبت شد.');
    }
}