<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\HomeworkSubmission;
use App\Models\Payment;
use App\Models\User;
use App\Support\JalaliDate;

final class DashboardService
{
    public function __construct(
        private readonly ClassroomWorkspaceService $classrooms,
        private readonly MessageWorkspaceService $messages,
    ) {}

    public function build(User $user): array
    {
        $teacherView = $this->classrooms->isTeacher($user);
        $classroomQuery = $this->classrooms->queryFor($user)->where('status', 'active');
        $courseQuery = $this->classrooms->coursesQueryFor($user);
        $classroomIds = (clone $classroomQuery)->select('classrooms.id');
        $courseIds = (clone $courseQuery)->select('courses.id');

        $canSeeStudents = $user->hasAnyPermission(['users.manage', 'students.view.assigned']);
        $canSeeClasses = $user->hasAnyPermission(['classes.manage', 'classes.view.assigned', 'classes.manage.assigned']);
        $canSeeCourses = $user->hasAnyPermission(['courses.manage', 'courses.view.assigned']);
        $canSeeAttendance = $user->hasAnyPermission(['attendance.view.all', 'attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned']);
        $canSeeHomework = $user->hasAnyPermission(['homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned', 'homework.grade']);
        $canSeeMessages = $user->hasAnyPermission(['messages.view.all', 'messages.view.assigned']);
        $canSeeAnnouncements = $user->hasAnyPermission(['announcements.manage', 'announcements.view']);

        $studentQuery = User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
            ->when($teacherView, fn ($query) => $query->where(function ($scope) use ($classroomIds, $courseIds): void {
                $scope->whereHas('enrolledClasses', fn ($classes) => $classes->whereIn('classrooms.id', $classroomIds))
                    ->orWhereHas('courseEnrollments', fn ($enrollments) => $enrollments
                        ->whereIn('course_id', $courseIds)->currentlyActive());
            }));

        $weekStart = today()->copy()->subDays(6)->startOfDay();
        $attendanceByDay = $canSeeAttendance
            ? Attendance::query()
                ->leftJoin('class_sessions as sessions', function ($join): void {
                    $join->on('sessions.id', '=', 'attendances.class_session_id')
                        ->on('sessions.classroom_id', '=', 'attendances.classroom_id');
                })
                ->when($teacherView, fn ($query) => $query->whereIn('attendances.classroom_id', $classroomIds))
                ->selectRaw(
                    "DATE(COALESCE(sessions.starts_at, attendances.session_date)) as attendance_day,
                    COUNT(*) as total,
                    SUM(CASE WHEN attendances.status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN attendances.status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN attendances.status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN attendances.status = 'excused' THEN 1 ELSE 0 END) as excused"
                )
                ->whereBetween('attendances.session_date', [$weekStart->toDateString(), today()->toDateString()])
                ->where(function ($query): void {
                    $query->whereNull('attendances.class_session_id')->orWhereNotNull('sessions.id');
                })
                ->where(function ($query): void {
                    $query->whereNull('sessions.id')->orWhere(function ($validSession): void {
                        $validSession->where('sessions.status', '!=', 'cancelled')->where('sessions.starts_at', '<=', now());
                    });
                })
                ->groupByRaw('DATE(COALESCE(sessions.starts_at, attendances.session_date))')
                ->get()
                ->keyBy('attendance_day')
            : collect();

        $attendanceTotal = (int) $attendanceByDay->sum('total');
        $attendancePresent = (int) ($attendanceByDay->sum('present') + $attendanceByDay->sum('late'));

        $canSeeSales = $user->hasPermissionTo('users.manage');
        $paidPayments = Payment::query()->where('status', 'paid')->whereNotNull('paid_at');
        if (! $canSeeSales) {
            $paidPayments->whereRaw('1 = 0');
        }
        $paidCoursePurchases = (int) (clone $paidPayments)->count();
        $averageCoursePurchase = (int) round((float) ((clone $paidPayments)->avg('amount') ?? 0));
        $revenueLast30Days = (int) (clone $paidPayments)
            ->whereBetween('paid_at', [now()->subDays(29)->startOfDay(), now()])
            ->sum('amount');
        $stats = [
            'students' => $canSeeStudents ? $studentQuery->count() : 0,
            'teachers' => $user->hasPermissionTo('users.manage')
                ? User::query()->where('is_active', true)->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->count()
                : 0,
            'classes' => $canSeeClasses ? (clone $classroomQuery)->count() : 0,
            'courses' => $canSeeCourses ? (clone $courseQuery)->where('status', 'published')->count() : 0,
            'attendance_rate' => $attendanceTotal > 0 ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0,
            'pending_homework' => $canSeeHomework
                ? HomeworkSubmission::query()
                    ->where('status', 'submitted')
                    ->when($teacherView, fn ($query) => $query->whereHas(
                        'homework',
                        fn ($homework) => $homework->whereIn('classroom_id', $classroomIds)
                    ))
                    ->count()
                : 0,
        ];

        // The dashboard is an operational overview, not a reporting workspace:
        // keep only scalar counts here and let detailed pages own their lists.
        $openMessageCount = $canSeeMessages
            ? $this->messages->queryFor($user)->where('status', 'open')->count()
            : 0;
        $pendingConsultationCount = $user->hasPermissionTo('consultations.manage')
            ? Consultation::query()->where('status', 'pending')->count()
            : 0;
        $publishedAnnouncementCount = $canSeeAnnouncements
            ? Announcement::query()->where('status', 'published')->count()
            : 0;

        $weeklyAttendance = $canSeeAttendance
            ? collect(range(6, 0))->map(function (int $daysAgo) use ($attendanceByDay): array {
                $date = today()->copy()->subDays($daysAgo);
                $attendance = $attendanceByDay->get($date->toDateString());
                $total = (int) ($attendance->total ?? 0);
                $present = (int) ($attendance->present ?? 0);

                $jalali = JalaliDate::fromGregorian($date);
                $dateLabel = sprintf('%04d/%02d/%02d', $jalali['year'], $jalali['month'], $jalali['day']);
                $dateLabel = strtr($dateLabel, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);

                return [
                    'label' => $date->translatedFormat('l'),
                    'date_label' => $dateLabel,
                    'date_iso' => $date->toDateString(),
                    'total' => $total,
                    'present' => (int) ($attendance->present ?? 0),
                    'late' => (int) ($attendance->late ?? 0),
                    'absent' => (int) ($attendance->absent ?? 0),
                    'excused' => (int) ($attendance->excused ?? 0),
                ];
            })
            : collect();

        return compact(
            'stats',
            'weeklyAttendance',
            'attendanceTotal',
            'openMessageCount',
            'pendingConsultationCount',
            'publishedAnnouncementCount',
            'paidCoursePurchases',
            'averageCoursePurchase',
            'revenueLast30Days',
        );
    }
}
