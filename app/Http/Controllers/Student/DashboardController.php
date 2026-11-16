<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\StudentDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());
        $data['student'] = $request->user();

        return view('student.dashboard', $data);
    }

    public function homework(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.assignments', [
            'homeworks' => $data['homeworks'],
            'student' => $request->user(),
        ]);
    }

    public function attendance(Request $request): View
    {
        $student = $request->user();
        $start = now()->startOfMonth();
        $records = Attendance::query()
            ->where('student_id', $student->id)
            ->whereBetween('session_date', [$start->toDateString(), now()->toDateString()])
            ->with(['classroom:id,name', 'session:id,starts_at'])
            ->get()
            ->keyBy(fn ($record) => $record->session_date->day);

        $calendar = collect(range(1, now()->daysInMonth))->map(function (int $day) use ($records): array {
            $record = $records->get($day);
            return [
                'day' => $day,
                'status' => match ($record?->status) {
                    'present' => 'present', 'late' => 'late', 'absent' => 'absent', default => 'none',
                },
            ];
        });

        $counts = $records->groupBy('status')->map->count();
        $total = (int) $counts->get('present', 0) + (int) $counts->get('late', 0) + (int) $counts->get('absent', 0);
        $rate = $total > 0 ? (int) round((($counts->get('present', 0) + $counts->get('late', 0)) / $total) * 100) : null;

        return view('student.attendance', [
            'calendar' => $calendar,
            'monthOffset' => ($start->dayOfWeek + 1) % 7,
            'monthTitle' => $start->translatedFormat('F Y'),
            'attendance_count' => $total,
            'attendance_rate' => $rate,
        ]);
    }

    public function results(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.results', ['results' => $data['results']]);
    }

    public function resources(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.resources', ['resources' => $data['resources']]);
    }

    public function liveClasses(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.live-classes', [
            'sessions' => $data['upcoming_sessions'],
            'classrooms' => $data['classrooms'],
        ]);
    }

    public function achievements(Request $request, StudentDashboardService $dashboard): View
    {
        $data = $dashboard->build($request->user());

        return view('student.achievements', [
            'course_progress' => $data['course_progress'],
            'attendance_rate' => $data['attendance_rate'],
            'results' => $data['results'],
        ]);
    }
}
