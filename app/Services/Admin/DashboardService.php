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

        $weeklyAttendanceQuery = Attendance::query()
            ->when($teacherView, fn ($query) => $query->whereIn('classroom_id', $classroomIds));

        $weekStart = today()->copy()->subDays(6);
        $attendanceByDay = $canSeeAttendance
            ? $weeklyAttendanceQuery
                ->selectRaw(
                    'session_date as attendance_day, COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as present',
                    ['present', 'late']
                )
                ->whereBetween('session_date', [$weekStart->toDateString(), today()->toDateString()])
                ->groupBy('session_date')->get()->keyBy('attendance_day')
            : collect();

        // The dashboard KPI is a seven-day aggregate; the chart below keeps
        // the per-day rates from the same grouped query.
        $attendanceTotal = (int) $attendanceByDay->sum('total');
        $attendancePresent = (int) $attendanceByDay->sum('present');

        $pendingHomework = $canSeeHomework
            ? HomeworkSubmission::query()
                ->where('status', 'submitted')
                ->when($teacherView, fn ($query) => $query->whereHas(
                    'homework',
                    fn ($homework) => $homework->whereIn('classroom_id', $classroomIds)
                ))
                ->count()
            : 0;

        $stats = [
            'students' => $canSeeStudents ? $studentQuery->count() : 0,
            'teachers' => $user->hasPermissionTo('users.manage')
                ? User::whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->where('is_active', true)->count()
                : 0,
            'classes' => $canSeeClasses ? (clone $classroomQuery)->count() : 0,
            'courses' => $canSeeCourses ? (clone $courseQuery)->where('status', 'published')->count() : 0,
            'attendance_rate' => $attendanceTotal > 0 ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0,
            'pending_homework' => $pendingHomework,
        ];

        $todayClasses = $canSeeClasses
            ? (clone $classroomQuery)->with(['teacher', 'course'])->withCount('students')->latest()->limit(6)->get()
            : collect();

        $openMessagesQuery = $canSeeMessages
            ? $this->messages->queryFor($user)->where('status', 'open')
            : null;
        $openMessageCount = $openMessagesQuery?->count() ?? 0;
        $recentMessages = $openMessagesQuery
            ? (clone $openMessagesQuery)->with('sender')->latest()->limit(5)->get()
            : collect();

        $pendingConsultationsQuery = $user->hasPermissionTo('consultations.manage')
            ? Consultation::query()->where('status', 'pending')
            : null;
        $pendingConsultationCount = $pendingConsultationsQuery?->count() ?? 0;
        $pendingConsultations = $pendingConsultationsQuery
            ? (clone $pendingConsultationsQuery)->with(['requester', 'student'])->latest()->limit(5)->get()
            : collect();

        $publishedAnnouncementsQuery = $canSeeAnnouncements
            ? Announcement::query()->where('status', 'published')
            : null;
        $publishedAnnouncementCount = $publishedAnnouncementsQuery?->count() ?? 0;
        $recentAnnouncements = $publishedAnnouncementsQuery
            ? (clone $publishedAnnouncementsQuery)->latest('published_at')->limit(4)->get()
            : collect();



        $weeklyAttendance = $canSeeAttendance
            ? collect(range(6, 0))->map(function (int $daysAgo): array {
                $date = today()->copy()->subDays($daysAgo);
                return ['date' => $date->toDateString(), 'label' => $date->translatedFormat('D')];
            })->map(function (array $day) use ($attendanceByDay): array {
                $attendance = $attendanceByDay->get($day['date']);
                $total = (int) ($attendance->total ?? 0);
                $present = (int) ($attendance->present ?? 0);
                return ['label' => $day['label'], 'rate' => $total > 0 ? (int) round(($present / $total) * 100) : 0];
            })
            : collect();

        return compact(
            'stats', 'todayClasses', 'recentMessages', 'pendingConsultations',
            'recentAnnouncements', 'weeklyAttendance', 'openMessageCount',
            'pendingConsultationCount', 'publishedAnnouncementCount'
        );
    }
}
