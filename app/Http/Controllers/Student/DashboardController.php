<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CourseEnrollment;
use App\Models\Payment;
use App\Services\Learning\LearningAccessService;
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
        return view('student.assignments', [
            'homeworks' => $dashboard->assignments($request->user()),
            'student' => $request->user(),
        ]);
    }

    public function attendance(Request $request, LearningAccessService $access): View
    {
        $student = $request->user();
        $classroomIds = $access->activeClassroomIds($student);
        $start = now()->startOfMonth();
        $monthRecords = Attendance::query()
            ->where('student_id', $student->id)
            ->whereIn('classroom_id', clone $classroomIds)
            ->whereBetween('session_date', [$start->toDateString(), now()->toDateString()])
            ->with(['classroom:id,name', 'session:id,starts_at'])
            ->get();
        $records = $monthRecords->groupBy(fn ($record) => $record->session_date->day);

        $calendar = collect(range(1, now()->daysInMonth))->map(function (int $day) use ($records): array {
            $statuses = $records->get($day, collect())->pluck('status');
            return [
                'day' => $day,
                'status' => $statuses->contains('absent') ? 'absent'
                    : ($statuses->contains('late') ? 'late'
                    : ($statuses->contains('present') ? 'present' : 'none')),
            ];
        });

        $counts = $monthRecords->groupBy('status')->map->count();
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
        $results = $data['results'];
        $scored = $results->filter(fn ($result) => $result->score !== null && $result->max_score > 0);
        $courseScores = $scored->groupBy(fn ($result) => $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? 'سایر')
            ->map(fn ($items, $name) => [
                'name' => $name,
                'score' => round($items->avg(fn ($item) => $item->score / $item->max_score * 20), 1),
            ])->values();

        return view('student.results', [
            'results' => $results,
            'averageScore' => $scored->isNotEmpty() ? round($scored->avg(fn ($result) => $result->score / $result->max_score * 20), 1) : null,
            'highestScore' => $scored->isNotEmpty() ? round($scored->max(fn ($result) => $result->score / $result->max_score * 20), 1) : null,
            'submittedHomeworkCount' => $data['homeworks']->filter(fn ($homework) => $homework->submitted_by_student_count > 0)->count(),
            'courseScores' => $courseScores,
        ]);
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
            'sessions' => $data['upcoming_sessions']->where('session_type', 'online')->values(),
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
    public function library(Request $request): View
    {
        $student = $request->user();
        $purchases = Payment::query()
            ->where('student_id', $student->id)
            ->with('course:id,title,slug,subject,grade')
            ->latest('created_at')
            ->paginate(12)
            ->withQueryString();

        $accesses = CourseEnrollment::query()
            ->where('student_id', $student->id)
            ->currentlyActive()
            ->with('course:id,title,slug,subject,grade')
            ->latest('granted_at')
            ->get();

        return view('student.library', compact('purchases', 'accesses'));
    }

}
