<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\Homework;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();

        $stats = [
            'students' => User::whereHas('roles', fn ($query) => $query->where('name', 'student'))->where('is_active', true)->count(),
            'teachers' => User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->where('is_active', true)->count(),
            'classes' => Classroom::where('status', 'active')->count(),
            'courses' => Course::where('status', 'published')->count(),
            'attendance_rate' => $this->attendanceRate($today),
            'pending_homework' => Homework::whereHas('submissions', fn ($query) => $query->where('status', 'submitted'))->count(),
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

        $weeklyAttendance = collect(range(6, 0))->map(function (int $daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            $total = Attendance::whereDate('session_date', $date)->count();
            $present = Attendance::whereDate('session_date', $date)
                ->whereIn('status', ['present', 'late'])->count();

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

    private function attendanceRate(Carbon $date): int
    {
        $total = Attendance::whereDate('session_date', $date)->count();

        if ($total === 0) {
            return 0;
        }

        $present = Attendance::whereDate('session_date', $date)
            ->whereIn('status', ['present', 'late'])->count();

        return (int) round(($present / $total) * 100);
    }
}
