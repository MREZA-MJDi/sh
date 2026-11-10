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

        $date = $request->date('date')?->toDateString() ?? today()->toDateString();
        $records = $classroom
            ? Attendance::where('classroom_id', $classroom->id)->whereDate('session_date', $date)->get()->keyBy('student_id')
            : collect();
        $history = Attendance::with(['student', 'classroom', 'marker'])
            ->when($classroom, fn ($query) => $query->where('classroom_id', $classroom->id))
            ->whereDate('session_date', $date)
            ->latest()->paginate(20)->withQueryString();

        return view('admin.attendance.index', compact('classrooms', 'classroom', 'date', 'records', 'history'));
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
