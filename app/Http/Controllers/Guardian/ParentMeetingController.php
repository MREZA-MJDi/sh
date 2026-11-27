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
            ->forGuardian($guardian->id, $selectedChild?->id ?? 0)
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
            ->limit(100)
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

        $availableClassrooms = Classroom::query()
            ->where('status', 'active')
            ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id));

        if (filled($data['classroom_id'] ?? null)) {
            $classroom = (clone $availableClassrooms)->findOrFail($data['classroom_id']);
        } else {
            $classroomOptions = (clone $availableClassrooms)->orderBy('id')->get();
            abort_if(
                $classroomOptions->count() > 1,
                422,
                'برای ارسال درخواست، کلاس مرتبط با فرزندتان را انتخاب کنید.'
            );
            $classroom = $classroomOptions->first();
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

        $notification = new ParentMeetingNotification($meeting, 'درخواست جدید جلسه از طرف ولی ثبت شد.');
        User::query()->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'admin'))
            ->orderBy('id')
            ->chunkById(100, fn ($admins) => Notification::send($admins, $notification));
        $student->notify(new ParentMeetingNotification($meeting, 'ولی شما درخواست جلسه‌ای را برای بررسی آموزشگاه ثبت کرده است.'));
        $classroom?->teacher?->notify(new ParentMeetingNotification(
            $meeting,
            'ولی یکی از دانش‌آموزان کلاس شما درخواست جلسه ثبت کرده است.'
        ));

        return redirect()->route('guardian.meetings.index', ['child' => $student->id])->with('success', 'درخواست جلسه برای بررسی آموزشگاه ثبت شد.');
    }
}