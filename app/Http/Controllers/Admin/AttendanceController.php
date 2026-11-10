<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $classrooms = Classroom::with('teacher')->where('status', '!=', 'archived')->orderBy('name')->get();

        $classroom = $request->filled('classroom_id')
            ? Classroom::with('students')->findOrFail($request->integer('classroom_id'))
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

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $classroom = Classroom::findOrFail($data['classroom_id']);

        foreach ($data['attendance'] as $studentId => $entry) {
            Attendance::updateOrCreate(
                [
                    'classroom_id' => $classroom->id,
                    'student_id' => $studentId,
                    'session_date' => $data['session_date'],
                ],
                [
                    'status' => $entry['status'],
                    'note' => $entry['note'] ?? null,
                    'marked_by' => auth()->id(),
                ]
            );
        }

        return redirect()->route('admin.attendance.index', [
            'classroom_id' => $classroom->id,
            'date' => $data['session_date'],
        ])->with('success', 'حضور و غیاب با موفقیت ذخیره شد.');
    }
}
