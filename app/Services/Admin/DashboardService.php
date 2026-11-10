<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\HomeworkSubmission;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Carbon;

final class DashboardService
{
    public function build(User $user): array
    {
        $teacherView = $user->hasRole('teacher') && ! $user->hasRole('admin');
        $classroomQuery = Classroom::query()->where('status', 'active');

        if ($teacherView) {
            $classroomQuery->where('teacher_id', $user->id);
        }

        $classroomIds = (clone $classroomQuery)->pluck('id');
        $studentQuery = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'student'));

        if ($teacherView) {
            $studentQuery->whereHas('enrolledClasses', fn ($query) => $query->whereIn('classrooms.id', $classroomIds));
        }

        $attendanceToday = Attendance::query()->whereDate('session_date', today());
        $weeklyAttendanceQuery = Attendance::query();

        if ($teacherView) {
            $attendanceToday->whereIn('classroom_id', $classroomIds);
            $weeklyAttendanceQuery->whereIn('classroom_id', $classroomIds);
        }

        $attendanceTotal = (clone $attendanceToday)->count();
        $attendancePresent = (clone $attendanceToday)->whereIn('status', ['present', 'late'])->count();

        $pendingHomework = HomeworkSubmission::query()
            ->where('status', 'submitted')
            ->when($teacherView, fn ($query) => $query->whereHas(
                'homework.classroom',
                fn ($classrooms) => $classrooms->where('teacher_id', $user->id)
            ))
            ->count();

        $stats = [
            'students' => $studentQuery->count(),
            'teachers' => $teacherView ? 1 : User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
                ->where('is_active', true)->count(),
            'classes' => (clone $classroomQuery)->count(),
            'courses' => Course::query()->where('status', 'published')
                ->when($teacherView, fn ($query) => $query->where('teacher_id', $user->id))
                ->count(),
            'attendance_rate' => $attendanceTotal > 0 ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0,
            'pending_homework' => $pendingHomework,
        ];

        $todayClasses = (clone $classroomQuery)
            ->with(['teacher', 'course'])
            ->withCount('students')
            ->latest()->limit(6)->get();

        $recentMessages = Message::with('sender')
            ->whereNull('parent_id')
            ->where('status', 'open')
            ->when($teacherView, function ($query) use ($classroomIds): void {
                $query->where(function ($scope) use ($classroomIds): void {
                    $scope->whereHas('sender.enrolledClasses', fn ($classes) => $classes->whereIn('classrooms.id', $classroomIds))
                        ->orWhereHas('sender.children.enrolledClasses', fn ($classes) => $classes->whereIn('classrooms.id', $classroomIds));
                });
            })
            ->latest()->limit(5)->get();

        $pendingConsultations = $teacherView
            ? collect()
            : Consultation::with(['requester', 'student'])
                ->where('status', 'pending')->latest()->limit(5)->get();

        $recentAnnouncements = Announcement::where('status', 'published')
            ->latest('published_at')->limit(4)->get();

        $weekStart = today()->copy()->subDays(6);
        $attendanceByDay = $weeklyAttendanceQuery
            ->selectRaw(
                'session_date as attendance_day, COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as present',
                ['present', 'late']
            )
            ->whereBetween('session_date', [$weekStart->toDateString(), today()->toDateString()])
            ->groupBy('session_date')
            ->get()
            ->keyBy('attendance_day');

        $weeklyAttendance = collect(range(6, 0))->map(function (int $daysAgo): array {
            $date = today()->copy()->subDays($daysAgo);
            return [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('D'),
            ];
        })->map(function (array $day) use ($attendanceByDay): array {
            $attendance = $attendanceByDay->get($day['date']);
            $total = (int) ($attendance->total ?? 0);
            $present = (int) ($attendance->present ?? 0);
            return [
                'label' => $day['label'],
                'rate' => $total > 0 ? (int) round(($present / $total) * 100) : 0,
            ];
        });

        return compact('stats', 'todayClasses', 'recentMessages', 'pendingConsultations', 'recentAnnouncements', 'weeklyAttendance');
    }
}
