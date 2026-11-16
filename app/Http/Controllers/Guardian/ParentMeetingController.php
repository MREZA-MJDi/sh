<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ParentMeeting;
use App\Models\User;
use App\Notifications\ParentMeetingNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Notification;

final class ParentMeetingController extends Controller
{
    public function index(Request $request): View
    {
        $guardian = $request->user();
        $children = $guardian->children()
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.grade']);

        $selectedChild = $children->firstWhere('id', $request->integer('child')) ?? $children->first();

        $meetings = ParentMeeting::query()
            ->where('guardian_id', $guardian->id)
            ->whereIn('student_id', $children->modelKeys())
            ->with(['student:id,name', 'classroom:id,name', 'course:id,title', 'creator:id,name', 'responder:id,name'])
            ->orderByRaw("CASE WHEN status IN ('requested', 'proposed') THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(starts_at, preferred_at) ASC')
            ->paginate(15)
            ->withQueryString();

        $classrooms = Classroom::query()
            ->where('status', 'active')
            ->whereHas('students', fn (Builder $students) => $students->whereKey($selectedChild?->id ?? 0))
            ->with('course:id,title')
            ->orderBy('name')
            ->get(['id', 'name', 'course_id']);

        return view('parent.meetings', [
            'kids' => $children,
            'kid' => $selectedChild,
            'meetings' => $meetings,
            'classrooms' => $classrooms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'message' => ['nullable', 'string', 'max:2000'],
            'preferred_at' => ['nullable', 'date', 'after:now'],
        ]);

        $guardian = $request->user();
        $student = $guardian->children()
            ->whereKey($data['student_id'])
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->firstOrFail();

        $classroom = null;
        if (! empty($data['classroom_id'])) {
            $classroom = Classroom::query()
                ->whereKey($data['classroom_id'])
                ->where('status', 'active')
                ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
                ->firstOrFail();
        }

        $meeting = ParentMeeting::query()->create([
            'classroom_id' => $classroom?->id,
            'course_id' => $classroom?->course_id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'title' => trim($data['title']),
            'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
            'preferred_at' => $data['preferred_at'] ?? null,
            'status' => 'requested',
        ]);

        $admins = User::query()->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'admin'))->get();
        Notification::send($admins, new ParentMeetingNotification($meeting, 'درخواست جدید جلسه از طرف ولی ثبت شد.'));

        return redirect()->route('guardian.meetings.index')->with('success', 'درخواست جلسه برای بررسی آموزشگاه ثبت شد.');
    }
}