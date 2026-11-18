<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Services\GuardianDashboardService;
use Illuminate\Http\Request;
use App\Support\JalaliDate;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);
        $data['kids'] = $data['children'];
        $data['kid'] = $data['selected_child'];

        $breakdown = $data['attendance_breakdown'];
        $data['attendance'] = [
            'present' => (int) $breakdown->get('present', 0),
            'late' => (int) $breakdown->get('late', 0),
            'absent' => (int) $breakdown->get('absent', 0),
            'percent' => $data['attendance_rate'] ?? 0,
        ];

        $scores = $data['results']->filter(fn ($result) => $result->score !== null)
            ->map(fn ($result) => $result->max_score > 0 ? round($result->score / $result->max_score * 20, 1) : null)
            ->filter();

        $data['stats'] = [
            'avg' => $scores->isNotEmpty() ? round($scores->avg(), 1) : '—',
            'avg_note' => $scores->isNotEmpty() ? 'بر اساس نتایج قابل مشاهده' : 'هنوز نمره‌ای ثبت نشده',
            'open_homework' => (int) $data['pending_homework_count'],
            'open_homework_note' => 'تکلیف بدون ارسال',
            'next_meeting' => optional($data['meetings']->firstWhere('status', 'scheduled')?->starts_at)->format('Y/m/d H:i') ?? '—',
            'next_meeting_note' => 'جلسه ثبت‌شده',
        ];

        $data['subjects'] = $data['results']->filter(fn ($result) => $result->exam?->course)
            ->groupBy(fn ($result) => $result->exam->course->title)
            ->map(fn ($items, $name) => [
                'name' => $name,
                'score' => round($items->avg(fn ($item) => $item->max_score > 0 ? $item->score / $item->max_score * 20 : 0), 1),
                'class_avg' => null,
            ])->values();

        $trend = $data['result_trend']->groupBy(function ($result): string {
            $date = JalaliDate::fromGregorian($result->created_at);
            return JalaliDate::monthKey($date['year'], $date['month']);
        });
        $currentJalali = JalaliDate::fromGregorian(now());
        $periods = collect(range(5, 0))->map(
            fn ($offset) => JalaliDate::monthKeyByOffset($currentJalali['year'], $currentJalali['month'], $offset)
        );
        $data['months'] = $periods->map(function (string $month): string {
            [$year, $monthNumber] = array_map('intval', explode('-', $month));
            return JalaliDate::monthLabel($year, $monthNumber);
        })->values();
        $data['scores'] = $periods->map(function ($month) use ($trend) {
            $items = $trend->get($month, collect());
            return $items->isEmpty() ? null : round($items->avg(fn ($item) => $item->max_score > 0 ? $item->score / $item->max_score * 20 : 0), 1);
        })->values();
        $data['classAvg'] = [];
        $homeworkTrend = $data['homework_trend'];
        $data['homeworkMonths'] = $data['months'];
        $data['hwOnTime'] = $periods->map(fn ($month) => (int) data_get($homeworkTrend->get($month, []), 'on_time', 0))->values();
        $data['hwLate'] = $periods->map(fn ($month) => (int) data_get($homeworkTrend->get($month, []), 'late', 0))->values();
        $data['hwMissing'] = $periods->map(fn ($month) => (int) data_get($homeworkTrend->get($month, []), 'missing', 0))->values();
        $data['teacherNotes'] = $data['reviewed_homework']->filter(fn ($submission) => filled($submission->feedback))
            ->map(fn ($submission) => [
                'teacher' => $submission->reviewer?->name ?? 'مدرس',
                'homework' => $submission->homework?->title,
                'text' => $submission->feedback,
                'score' => $submission->score,
                'type' => 'feedback',
            ]);

        return view('parent.dashboard', $data);
    }

    public function attendance(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);
        $now = now();
        $jalaliNow = JalaliDate::fromGregorian($now);
        $start = JalaliDate::toGregorian($jalaliNow['year'], $jalaliNow['month'], 1);
        $monthDays = JalaliDate::daysInMonth($jalaliNow['year'], $jalaliNow['month']);
        $todayJalaliDay = $jalaliNow['day'];

        $records = $data['attendance_calendar']->groupBy(
            fn ($record) => JalaliDate::fromGregorian($record->session_date)['day']
        );
        $days = collect(range(1, $monthDays))->map(function (int $day) use ($records, $todayJalaliDay): array {
            $dayRecords = $records->get($day, collect());
            $statuses = $dayRecords->pluck('status');
            $status = $statuses->contains('absent') ? 'a'
                : ($statuses->contains('late') ? 'l'
                : ($statuses->contains('present') ? 'p' : 'future'));
            return ['day' => $day, 'status' => $status, 'today' => $day === $todayJalaliDay];
        });

        $monthBreakdown = $data['attendance_calendar']->groupBy('status')->map->count();
        $monthTotal = (int) $monthBreakdown->get('present', 0) + (int) $monthBreakdown->get('late', 0) + (int) $monthBreakdown->get('absent', 0);
        $monthAttendance = [
            'present' => (int) $monthBreakdown->get('present', 0),
            'late' => (int) $monthBreakdown->get('late', 0),
            'absent' => (int) $monthBreakdown->get('absent', 0),
            'percent' => $monthTotal > 0 ? (int) round((($monthBreakdown->get('present', 0) + $monthBreakdown->get('late', 0)) / $monthTotal) * 100) : 0,
        ];

        return view('parent.attendance', [
            'kid' => $data['selected_child'], 'kids' => $data['children'],
            'attendance' => $monthAttendance,
            'calendar' => $days, 'monthOffset' => ($start->dayOfWeek + 1) % 7,
            'monthTitle' => JalaliDate::monthLabel($jalaliNow['year'], $jalaliNow['month']),
            'absencePattern' => $data['attendance_breakdown']->get('absent', 0).' غیبت ثبت‌شده',
            'lastLate' => $data['attendance']->firstWhere('status', 'late')?->session_date?->format('Y/m/d') ?? 'موردی ثبت نشده',
        ]);
    }

    public function exams(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);
        $subjects = $data['results']->filter(fn ($result) => $result->exam?->course)
            ->groupBy(fn ($result) => $result->exam->course->title)
            ->map(fn ($items, $name) => [
                'name' => $name,
                'score' => round($items->avg(fn ($item) => $item->max_score > 0 ? $item->score / $item->max_score * 20 : 0), 1),
                'class_avg' => null,
            ])->values();

        return view('parent.exams', [
            'kid' => $data['selected_child'], 'kids' => $data['children'],
            'subjects' => $subjects, 'results' => $data['results'],
        ]);
    }

    public function homework(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);
        $homework = $data['homeworks']->map(function ($item): array {
            $submission = $item->submissions->first();
            $late = $submission && $item->due_at && $submission->submitted_at && $submission->submitted_at->gt($item->due_at);
            $missing = ! $submission && $item->due_at && $item->due_at->isPast();
            return [
                'title' => $item->title,
                'due' => $item->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت',
                'status' => $submission ? ($late ? 'ارسال با تأخیر' : 'ارسال‌شده') : ($missing ? 'ارسال‌نشده' : 'در انتظار'),
                'tone' => $submission ? ($late ? 'am' : 'gn') : ($missing ? 'rd' : 'bl'),
            ];
        });

        $totals = $data['homework_trend']->reduce(fn (array $carry, array $row) => [
            'on_time' => $carry['on_time'] + $row['on_time'],
            'late' => $carry['late'] + $row['late'],
            'missing' => $carry['missing'] + $row['missing'],
        ], ['on_time' => 0, 'late' => 0, 'missing' => 0]);

        return view('parent.homework', [
            'kid' => $data['selected_child'], 'kids' => $data['children'],
            'homework' => $homework, 'hwTotals' => $totals,
        ]);
    }

    public function resources(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);

        return view('parent.resources', [
            'kids' => $data['children'],
            'kid' => $data['selected_child'],
            'resources' => $data['resources'],
        ]);
    }

    private function build(Request $request, GuardianDashboardService $dashboard): array
    {
        $validated = $request->validate(['child' => ['nullable', 'integer', 'min:1']]);
        return $dashboard->build(
            $request->user(),
            isset($validated['child']) ? (int) $validated['child'] : null
        );
    }

    private function attendanceSummary(array $data): array
    {
        $breakdown = $data['attendance_breakdown'];
        return [
            'present' => (int) $breakdown->get('present', 0),
            'late' => (int) $breakdown->get('late', 0),
            'absent' => (int) $breakdown->get('absent', 0),
            'percent' => $data['attendance_rate'] ?? 0,
        ];
    }
}
