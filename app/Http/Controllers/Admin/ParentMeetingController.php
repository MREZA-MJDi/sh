<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParentMeeting;
use App\Models\User;
use App\Notifications\ParentMeetingNotification;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ParentMeetingController extends Controller
{
    public function index(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $actor = $request->user();
        $selectedMeetingId = $request->integer('meeting');
        $classrooms = $workspace->queryFor($actor)
            ->where('classrooms.status', 'active')
            ->orderBy('classrooms.name')
            ->get(['classrooms.id', 'classrooms.name']);

        $classroomCount = $classrooms->count();

        $meetings = ParentMeeting::query()
            ->when($selectedMeetingId > 0, fn (Builder $query) => $query->whereKey($selectedMeetingId))
            ->when($workspace->isTeacher($actor), fn (Builder $query) => $query->whereIn(
                'classroom_id',
                $workspace->queryFor($actor)->select('classrooms.id')
            ))
            ->with(['student:id,name', 'guardian:id,name', 'classroom:id,name', 'course:id,title', 'creator:id,name'])
            ->orderByRaw("CASE WHEN status IN ('requested', 'proposed') THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(starts_at, preferred_at) ASC')
            ->paginate(20)
            ->withQueryString();

        return view('admin.parent-meetings.index', compact('classrooms', 'classroomCount', 'meetings'));
    }

    public function lookup(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
            'classroom_id' => ['nullable', 'integer'],
        ]);

        $actor = $request->user();
        $isTeacher = $workspace->isTeacher($actor);
        abort_unless(! $isTeacher || filled($data['classroom_id'] ?? null), 422, 'ابتدا کلاس موردنظر را انتخاب کنید.');

        $classroom = null;
        if (filled($data['classroom_id'] ?? null)) {
            $classroom = $workspace->queryFor($actor)
                ->where('classrooms.status', 'active')
                ->findOrFail((int) $data['classroom_id']);
        }

        $term = trim($data['q']);
        $students = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->when($classroom, fn (Builder $query) => $query->whereIn(
                'users.id',
                DB::table('classroom_student')->select('student_id')->where('classroom_id', $classroom->id)
            ))
            ->where(fn (Builder $search) => $search
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"))
            ->with(['guardians' => fn ($guardians) => $guardians
                ->where('users.is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))
                ->orderBy('users.name')
                ->select('users.id', 'users.name')])
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name']);

        return response()->json([
            'data' => $students->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'guardians' => $student->guardians->map(fn (User $guardian) => [
                    'id' => $guardian->id,
                    'name' => $guardian->name,
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'guardian_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'min:3', 'max:180'],
            'message' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        abort_unless(! $workspace->isTeacher($request->user()) || ! empty($data['classroom_id']), 422, 'مدرس باید کلاس مرتبط با جلسه را انتخاب کند.');

        $classroom = null;
        if (! empty($data['classroom_id'])) {
            $classroom = $workspace->queryFor($request->user())
                ->where('status', 'active')
                ->findOrFail($data['classroom_id']);
            abort_unless($classroom->students()->whereKey($data['student_id'])->exists(), 422, 'دانش‌آموز عضو این کلاس نیست.');
        }

        $student = User::query()
            ->whereKey($data['student_id'])
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->firstOrFail();
        $guardian = $student->guardians()
            ->whereKey($data['guardian_id'])
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))
            ->firstOrFail();

        $isTeacher = $workspace->isTeacher($request->user());
        $meeting = ParentMeeting::query()->create([
            'classroom_id' => $classroom?->id,
            'course_id' => $classroom?->course_id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'created_by' => $request->user()->id,
            'title' => trim($data['title']),
            'message' => filled($data['message'] ?? null) ? trim($data['message']) : null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'status' => $isTeacher ? 'proposed' : 'scheduled',
        ]);

        $guardian->notify(new ParentMeetingNotification($meeting, $isTeacher
            ? 'مدرس برای گفت‌وگو درباره وضعیت آموزشی فرزند پیشنهاد جلسه ثبت کرده است.'
            : 'جلسه‌ای برای شما و فرزندتان زمان‌بندی شده است.'));
        $student->notify(new ParentMeetingNotification($meeting, $isTeacher
            ? 'مدرس برای گفت‌وگو درباره وضعیت آموزشی شما پیشنهاد جلسه ثبت کرده است.'
            : 'جلسه‌ای با ولی شما زمان‌بندی شده است.'));

        return back()->with('success', $isTeacher
            ? 'پیشنهاد جلسه برای بررسی مدیر ثبت شد.'
            : 'جلسه با موفقیت برای ولی دانش‌آموز زمان‌بندی شد.');
    }

    public function updateStatus(Request $request, int $meeting): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $record = ParentMeeting::query()->findOrFail($meeting);
        $isPending = in_array($record->status, ['requested', 'proposed'], true);
        $isScheduling = $request->input('status') === 'scheduled'
            && ($isPending || $record->status === 'scheduled');

        $data = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled', 'declined'])],
            'starts_at' => [Rule::excludeIf(! $isScheduling), 'required', 'date', 'after:now'],
            'ends_at' => [Rule::excludeIf(! $isScheduling), 'nullable', 'date', 'after:starts_at'],
        ]);

        if ($isPending) {
            abort_unless(in_array($data['status'], ['scheduled', 'declined', 'cancelled'], true), 422);
        } elseif ($record->status === 'scheduled') {
            abort_unless(in_array($data['status'], ['scheduled', 'completed', 'cancelled'], true), 422);
        } else {
            abort(422, 'وضعیت این جلسه قابل تغییر نیست.');
        }

        $record->update([
            'status' => $data['status'],
            'starts_at' => $data['starts_at'] ?? $record->starts_at ?? $record->preferred_at,
            'ends_at' => array_key_exists('ends_at', $data)
                ? $data['ends_at']
                : ($isScheduling && isset($data['starts_at']) ? null : $record->ends_at),
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ]);

        $record->loadMissing(['guardian', 'student']);
        $notification = new ParentMeetingNotification($record, 'وضعیت درخواست یا زمان جلسه شما به‌روزرسانی شد.');
        $record->guardian?->notify($notification);
        $record->student?->notify(new ParentMeetingNotification($record, 'وضعیت جلسه مرتبط با شما به‌روزرسانی شد.'));

        return back()->with('success', 'وضعیت جلسه به‌روزرسانی شد.');
    }
}
