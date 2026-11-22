<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\ParentMeeting;
use App\\Models\\User;
use App\\Services\\Admin\\ClassroomWorkspaceService;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\View\\View;

class ParentMeetingController extends Controller
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

        $meetingsQuery = ParentMeeting::query()
            ->when($workspace->isTeacher($actor), fn (Builder $query) => $query->whereIn(
                'classroom_id',
                $workspace->queryFor($actor)->select('classrooms.id')
            ));

        $stats = [
            'total' => (clone $meetingsQuery)->count(),
            'pending' => (clone $meetingsQuery)->whereIn('status', ['requested', 'proposed'])->count(),
            'scheduled' => (clone $meetingsQuery)->where('status', 'scheduled')->count(),
        ];

        $meetings = (clone $meetingsQuery)
            ->when($selectedMeetingId > 0, fn (Builder $query) => $query->whereKey($selectedMeetingId))
            ->with(['student:id,name', 'guardian:id,name', 'classroom:id,name', 'course:id,title', 'creator:id,name'])
            ->orderByRaw("CASE WHEN status IN ('requested', 'proposed') THEN 0 WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(starts_at, preferred_at) ASC')
            ->paginate(20)
            ->withQueryString();

        return view('admin.parent-meetings.index', compact('classrooms', 'classroomCount', 'meetings', 'stats'));
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
            'title' => ['required', 'string', 'max:180'],
            'message' => ['nullable', 'string', 'max:4000'],
            'preferred_at' => ['nullable', 'date'],
        ]);

        $actor = $request->user();
        $classroom = null;
        if (filled($data['classroom_id'] ?? null)) {
            $classroom = $workspace->queryFor($actor)->findOrFail((int) $data['classroom_id']);
        }
        abort_unless(! $workspace->isTeacher($actor) || $classroom, 422);

        $student = User::query()->whereKey($data['student_id'])
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'student'))
            ->when($classroom, fn (Builder $query) => $query->whereIn(
                'users.id',
                DB::table('classroom_student')->select('student_id')->where('classroom_id', $classroom->id)
            ))
            ->firstOrFail();

        $guardian = $student->guardians()
            ->whereKey($data['guardian_id'])
            ->where('users.is_active', true)
            ->whereHas('roles', fn (Builder $roles) => $roles->where('name', 'parent'))
            ->firstOrFail();

        ParentMeeting::query()->create([
            'classroom_id' => $classroom?->id,
            'course_id' => $classroom?->course_id,
            'student_id' => $student->id,
            'guardian_id' => $guardian->id,
            'created_by' => $actor->id,
            'title' => $data['title'],
            'message' => $data['message'] ?? null,
            'preferred_at' => $data['preferred_at'] ?? null,
            'status' => 'requested',
        ]);

        return back()->with('success', 'درخواست جلسه ثبت شد.');
    }

    public function updateStatus(Request $request, ParentMeeting $meeting): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:requested,proposed,scheduled,completed,declined,cancelled'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'meeting_url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $meeting->update($data + ['handled_by' => $request->user()->id]);

        return back()->with('success', 'وضعیت جلسه به‌روزرسانی شد.');
    }
}
