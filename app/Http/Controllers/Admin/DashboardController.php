<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Message;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();
        $weekStart = $today->copy()->subDays(6);

        $attendanceByDay = Attendance::query()
            ->selectRaw(
                'session_date as attendance_day, COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as present',
                ['present', 'late']
            )
            ->whereBetween('session_date', [$weekStart->toDateString(), $today->toDateString()])
            ->groupBy('session_date')
            ->get()
            ->keyBy('attendance_day');

        $todayAttendance = $attendanceByDay->get($today->toDateString());
        $todayTotal = (int) ($todayAttendance->total ?? 0);
        $todayPresent = (int) ($todayAttendance->present ?? 0);

        $stats = [
            'students' => User::whereHas('roles', fn ($query) => $query->where('name', 'student'))->where('is_active', true)->count(),
            'teachers' => User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->where('is_active', true)->count(),
            'classes' => Classroom::where('status', 'active')->count(),
            'courses' => Course::where('status', 'published')->count(),
            'attendance_rate' => $todayTotal > 0 ? (int) round(($todayPresent / $todayTotal) * 100) : 0,
            'pending_homework' => HomeworkSubmission::where('status', 'submitted')->count(),
        ];

        $todayClasses = Classroom::with(['teacher', 'course'])
            ->withCount('students')
            ->where('status', 'active')
            ->latest()->limit(6)->get();

        $recentMessages = Message::with('sender')
            ->whereNull('parent_id')->where('status', 'open')->latest()->limit(5)->get();

        $pendingConsultations = Consultation::with(['requester', 'student'])
            ->where('status', 'pending')->latest()->limit(5)->get();

        $recentAnnouncements = Announcement::where('status', 'published')
            ->latest('published_at')->limit(4)->get();

        $weeklyAttendance = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $attendanceByDay) {
            $date = $today->copy()->subDays($daysAgo);
            $attendance = $attendanceByDay->get($date->toDateString());
            $total = (int) ($attendance->total ?? 0);
            $present = (int) ($attendance->present ?? 0);

            return [
                'label' => $date->translatedFormat('D'),
                'rate' => $total > 0 ? (int) round(($present / $total) * 100) : 0,
            ];
        });

        return view('admin.dashboard.index', compact(
            'stats', 'todayClasses', 'recentMessages',
            'pendingConsultations', 'recentAnnouncements', 'weeklyAttendance'
        ));
    }

}
