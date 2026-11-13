<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Models\Attendance;
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
            ->with('teacher')->where('status', '!=', 'archived')->orderBy('name')->get();

        $classroom = $request->filled('classroom_id')
            ? $workspace->queryFor($request->user())->with('students')->findOrFail($request->integer('classroom_id'))
            : $classrooms->first();

        $classroom?->loadMissing('students');
        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $records = $classroom
            ? Attendance::with('classSession')->where('classroom_id', $classroom->id)->whereDate('session_date', $date)->get()->keyBy('student_id')
            : collect();
        $measured = $records->filter(fn (Attendance $record): bool => $record->attended_minutes !== null);
        $studentCount = $classroom?->students->count() ?? 0;
        $attendedCount = $records->whereIn('status', ['present', 'late'])->count();
        $countableRecords = $records->reject(fn (Attendance $record): bool => $record->status === 'excused')->count();
        $summary = [
            'students' => $studentCount,
            'marked' => $records->count(),
            'attended' => $attendedCount,
            'attendance_rate' => $countableRecords > 0 ? round(($attendedCount / $countableRecords) * 100, 1) : null,
            'measured_count' => $measured->count(),
            'average_minutes' => $measured->isNotEmpty() ? (int) round($measured->avg('attended_minutes')) : null,
        ];

        $history = Attendance::with(['student', 'classroom', 'marker', 'classSession'])
            ->whereIn('classroom_id', $workspace->queryFor($request->user())->select('classrooms.id'))
            ->when($classroom, fn ($query) => $query->where('classroom_id', $classroom->id))
            ->whereDate('session_date', $date)
            ->latest()->paginate(20)->withQueryString();

        return view('admin.attendance.index', compact('classrooms', 'classroom', 'date', 'records', 'history', 'summary'));
    }

    public function store(
        StoreAttendanceRequest $request,
        AttendanceService $attendance,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $data = $request->validated();
        $classroom = $workspace->queryFor($request->user())->findOrFail($data['classroom_id']);
        $attendance->record($classroom, $data['session_date'], $data['attendance'], (int) $request->user()->id);

        return redirect()->route('admin.attendance.index', [
            'classroom_id' => $classroom->id,
            'date' => $data['session_date'],
        ])->with('success', 'حضور و غیاب با موفقیت ذخیره شد.');
    }
}
