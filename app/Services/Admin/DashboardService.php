<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\HomeworkSubmission;
use App\Models\User;

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

        $weekStart = today()->copy()->subDays(6);
        $attendanceByDay = $canSeeAttendance
            ? Attendance::query()
                ->when($teacherView, fn ($query) => $query->whereIn('classroom_id', $classroomIds))
                ->selectRaw(
                    'session_date as attendance_day, COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as present',
                    ['present', 'late']
                )
                ->whereBetween('session_date', [$weekStart->toDateString(), today()->endOfDay()->toDateTimeString()])
                ->groupBy('session_date')
                ->get()
                ->keyBy('attendance_day')
            : collect();

        $attendanceTotal = (int) $attendanceByDay->sum('total');
        $attendancePresent = (int) $attendanceByDay->sum('present');

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

                return [
                    'label' => $date->translatedFormat('D'),
                    'rate' => $total > 0 ? (int) round(($present / $total) * 100) : 0,
                ];
            })
            : collect();

        return compact(
            'stats',
            'weeklyAttendance',
            'openMessageCount',
            'pendingConsultationCount',
            'publishedAnnouncementCount',
        );
    }
}
