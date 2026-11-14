<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\ParentMeeting;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Database\Eloquent\Builder;
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
        $classrooms = $workspace->queryFor($actor)
            ->where('status', 'active')
            ->with(['course:id,title', 'students:id,name'])
            ->orderBy('name')
            ->get();

        $studentIds = $classrooms->flatMap(fn (Classroom $classroom) => $classroom->students->modelKeys())->unique();
        $students = User::query()
            ->whereIn('id', $studentIds)
            ->where('is_active', true)
            ->with(['guardians' => fn (Builder $guardians) => $guardians
                ->where('is_active', true)
                ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))
                ->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'grade']);

        $meetings = ParentMeeting::query()
            ->when($workspace->isTeacher($actor), fn (Builder $query) => $query->whereIn(
                'classroom_id',
                $workspace->queryFor($actor)->select('classrooms.id')
            ))
            ->with(['student:id,name', 'guardian:id,name', 'classroom:id,name', 'course:id,title', 'creator:id,name'])
            ->orderByRaw("CASE WHEN status IN ('requested', 'proposed') THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(starts_at, preferred_at) ASC')
            ->paginate(20)
            ->withQueryString();

        return view('admin.parent-meetings.index', compact('classrooms', 'students', 'meetings'));
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
        ParentMeeting::query()->create([
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

        return back()->with('success', $isTeacher
            ? 'پیشنهاد جلسه برای بررسی مدیر ثبت شد.'
            : 'جلسه با موفقیت برای ولی دانش‌آموز زمان‌بندی شد.');
    }

    public function updateStatus(Request $request, int $meeting, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $record = ParentMeeting::query()
            ->when($workspace->isTeacher($request->user()), fn (Builder $query) => $query->whereIn(
                'classroom_id',
                $workspace->queryFor($request->user())->select('classrooms.id')
            ))
            ->findOrFail($meeting);

        $data = $request->validate([
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled', 'declined'])],
            'starts_at' => ['nullable', 'date', Rule::requiredIf(fn () => in_array($record->status, ['requested', 'proposed'], true) && $request->input('status') === 'scheduled'), 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        if (in_array($record->status, ['requested', 'proposed'], true)) {
            abort_unless(in_array($data['status'], ['scheduled', 'declined', 'cancelled'], true), 422);
            if ($data['status'] === 'scheduled') {
                abort_unless(filled($data['starts_at'] ?? null), 422, 'زمان جلسه را مشخص کنید.');
            }
        } else {
            abort_unless(in_array($data['status'], ['completed', 'cancelled'], true), 422, 'وضعیت این جلسه قابل تغییر نیست.');
        }

        $record->update([
            'status' => $data['status'],
            'starts_at' => $data['starts_at'] ?? $record->starts_at,
            'ends_at' => $data['ends_at'] ?? $record->ends_at,
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ]);

        return back()->with('success', 'وضعیت جلسه به‌روزرسانی شد.');
    }
}