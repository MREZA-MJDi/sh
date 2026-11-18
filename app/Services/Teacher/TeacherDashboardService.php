<?php

namespace App\Services\Teacher;

use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\Course;
use App\Models\HomeworkSubmission;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\MessageWorkspaceService;
use Illuminate\Database\Eloquent\Builder;

final class TeacherDashboardService
{
    public function __construct(
        private readonly ClassroomWorkspaceService $classrooms,
        private readonly MessageWorkspaceService $messages,
    ) {}

    private function courseLabel(?Course $course): string
    {
        if (! $course) {
            return 'جلسه آموزشی';
        }

        $seasonLabels = ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'];
        $parts = [$course->title];

        if (filled($course->grade)) {
            $parts[] = 'پایه '.$course->grade;
        }

        if (filled($course->season)) {
            $parts[] = $seasonLabels[$course->season] ?? $course->season;
        }

        return implode(' · ', $parts);
    }

    public function build(User $teacher): array
    {
        $activeClassrooms = $this->classrooms->queryFor($teacher)
            ->where('classrooms.status', 'active');
        $classroomIds = (clone $activeClassrooms)->select('classrooms.id');

        $classroomCount = (clone $activeClassrooms)->count();

        $todayClasses = (clone $activeClassrooms)
            ->with('course:id,title,grade,season')
            ->withCount('students')
            ->orderBy('classrooms.name')
            ->limit(6)
            ->get()
            ->map(fn ($classroom) => [
                'title' => $classroom->name,
                'time' => $this->courseLabel($classroom->course),
                'status' => filled($classroom->meeting_url) ? 'link' : 'scheduled',
                'url' => $classroom->meeting_url,
            ]);

        $todaySessions = ClassSession::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->whereDate('starts_at', today())
            ->where('status', 'scheduled')
            ->with(['classroom:id,name,meeting_url,course_id', 'classroom.course:id,title,grade,season'])
            ->orderBy('starts_at')
            ->limit(12)
            ->get()
            ->map(fn (ClassSession $session) => [
                'title' => $session->title ?: $session->classroom?->name,
                'time' => $session->starts_at?->format('H:i').' · '.$this->courseLabel($session->classroom?->course),
                'status' => $session->session_type === 'online'
                    ? (filled($session->meeting_url) || filled($session->classroom?->meeting_url)
                        ? 'link'
                        : ($session->meeting_provider === 'jitsi' ? 'waiting_jitsi' : 'waiting'))
                    : 'onsite',
                'url' => $session->meeting_url ?: $session->classroom?->meeting_url,
            ]);

        $calendarStart = today()->copy()->startOfDay();
        $calendarEnd = today()->copy()->addDays(6)->endOfDay();
        $calendarSessions = ClassSession::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->where('status', 'scheduled')
            ->whereBetween('starts_at', [$calendarStart, $calendarEnd])
            ->with(['classroom:id,name', 'classroom.course:id,title'])
            ->orderBy('starts_at')->get();

        $weekCalendar = collect(range(0, 6))->map(function (int $offset) use ($calendarSessions): array {
            $date = today()->copy()->addDays($offset);
            $sessions = $calendarSessions->filter(
                fn (ClassSession $session) => $session->starts_at?->isSameDay($date)
            )->values();

            return ['date' => $date->toDateString(), 'count' => $sessions->count(), 'sessions' => $sessions];
        });

        $pendingSubmissions = HomeworkSubmission::query()
            ->where('status', 'submitted')
            ->whereHas('homework', fn (Builder $homework) => $homework->whereIn('classroom_id', clone $classroomIds));

        $pendingHomeworkCount = (clone $pendingSubmissions)->count();

        $pendingHomework = (clone $pendingSubmissions)
            ->selectRaw('homework_id, COUNT(*) as submitted_count, MAX(submitted_at) as latest_submission_at')
            ->with('homework:id,title,classroom_id')
            ->groupBy('homework_id')
            ->orderByDesc('latest_submission_at')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'title' => $item->homework?->title ?? 'تکلیف',
                'submitted' => (int) $item->submitted_count,
                'type' => 'در انتظار بررسی',
            ]);

        $attendanceToday = Attendance::query()
            ->whereIn('classroom_id', clone $classroomIds)
            ->whereDate('session_date', today());

        $absentToday = (clone $attendanceToday)->where('status', 'absent')->count();

        $weekStart = today()->copy()->subDays(6);
        $attendanceByDay = Attendance::query()
            ->selectRaw(
                'session_date as attendance_day, COUNT(*) as total, SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as present',
                ['present', 'late']
            )
            ->whereIn('classroom_id', clone $classroomIds)
            ->whereBetween('session_date', [$weekStart->toDateString(), today()->toDateString()])
            ->groupBy('session_date')
            ->get()
            ->keyBy(fn ($row) => (string) $row->attendance_day);

        $weekAttendance = collect(range(6, 0))->map(function (int $daysAgo) use ($attendanceByDay): array {
            $date = today()->copy()->subDays($daysAgo);
            $row = $attendanceByDay->get($date->toDateString());
            $total = (int) ($row->total ?? 0);
            $present = (int) ($row->present ?? 0);

            return [
                'label' => $date->translatedFormat('D'),
                'percent' => $total > 0 ? (int) round(($present / $total) * 100) : 0,
            ];
        });

        $openMessages = $this->messages->queryFor($teacher)->where('status', 'open');
        $newMessageCount = (clone $openMessages)->count();
        $newMessages = (clone $openMessages)
            ->with('sender:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($message) => [
                'from' => $message->sender?->name ?? 'کاربر',
                'text' => $message->subject ?: $message->body,
            ]);

        return [
            'teacher' => $teacher,
            'stats' => [
                'classes_today' => $classroomCount,
                'classes_today_note' => 'کلاس‌های فعال تحت مدیریت شما',
                'homework_review' => $pendingHomeworkCount,
                'homework_review_note' => 'ارسال در انتظار بررسی',
                'absent_today' => $absentToday,
                'absent_today_note' => 'در کلاس‌های شما',
                'new_messages' => $newMessageCount,
                'new_messages_note' => 'پیام‌های باز',
            ],
            'todayClasses' => $todayClasses,
            'todaySessions' => $todaySessions,
            'weekCalendar' => $weekCalendar,
            'weekAttendance' => $weekAttendance,
            'pendingHomework' => $pendingHomework,
            'newMessages' => $newMessages,
        ];
    }
}
