<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Services\Admin\AttendanceService;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classrooms = $workspace->queryFor($request->user())
            ->with('teacher')
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get();

        $classroom = $request->filled('classroom_id')
            ? $workspace->queryFor($request->user())->with('students')->findOrFail($request->integer('classroom_id'))
            : $classrooms->first();

        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $sessions = $classroom
            ? ClassSession::query()
                ->where('classroom_id', $classroom->id)
                ->whereDate('starts_at', $date)
                ->where('status', '!=', 'cancelled')
                ->orderBy('starts_at')
                ->get()
            : collect();

        $session = $sessions->firstWhere('id', $request->integer('class_session_id')) ?? $sessions->first();

        $records = $session
            ? Attendance::query()
                ->where('class_session_id', $session->id)
                ->with('session')
                ->get()
                ->keyBy('student_id')
            : collect();

        $history = Attendance::query()
            ->with(['student', 'classroom', 'marker', 'session'])
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->when($classroom, fn ($query) => $query->where('classroom_id', $classroom->id))
            ->when($session, fn ($query) => $query->where('class_session_id', $session->id))
            ->when(! $session, fn ($query) => $query->whereDate('session_date', $date))
            ->latest('session_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.attendance.index', compact('classrooms', 'classroom', 'date', 'sessions', 'session', 'records', 'history'));
    }

    public function store(
        StoreAttendanceRequest $request,
        AttendanceService $attendance,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $data = $request->validated();
        $classroom = $workspace->queryFor($request->user())->findOrFail($data['classroom_id']);
        $session = ClassSession::query()
            ->where('classroom_id', $classroom->id)
            ->findOrFail($data['class_session_id']);

        $attendance->record($classroom, $session, $data['attendance'], (int) $request->user()->id);

        return redirect()->route('admin.attendance.index', [
            'classroom_id' => $classroom->id,
            'date' => $session->starts_at->toDateString(),
            'class_session_id' => $session->id,
        ])->with('success', 'حضور و غیاب این جلسه با زمان‌های واقعی ثبت‌شده ذخیره شد.');
    }
}