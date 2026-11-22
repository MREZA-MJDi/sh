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
        $guardian = $request->user();
        if (! $guardian->hasPermissionTo('exams.view.children')) {
            $data['results'] = collect();
            $data['result_trend'] = collect();
            $data['average_exam_score'] = null;
        }
        if (! $guardian->hasPermissionTo('homework.view.children')) {
            $data['homework_trend'] = collect();
            $data['reviewed_homework'] = collect();
            $data['pending_homework_count'] = null;
        }
        if (! $guardian->hasPermissionTo('attendance.view.children')) {
            $data['attendance_breakdown'] = collect();
            $data['attendance_rate'] = null;
            $data['attendance_count'] = 0;
        }
        $data['kids'] = $data['children'];
        $data['kid'] = $data['selected_child'];

        $breakdown = $data['attendance_breakdown'];
        $data['attendance'] = [
            'present' => $breakdown->isNotEmpty() ? (int) $breakdown->get('present', 0) : null,
            'late' => $breakdown->isNotEmpty() ? (int) $breakdown->get('late', 0) : null,
            'absent' => $breakdown->isNotEmpty() ? (int) $breakdown->get('absent', 0) : null,
            'percent' => $data['attendance_rate'],
        ];

        $averageExamScore = $data['average_exam_score'];

        $data['stats'] = [
            'avg' => $averageExamScore !== null ? round((float) $averageExamScore, 1) : '—',
            'avg_note' => $averageExamScore !== null
                ? 'میانگین همه نتایج قابل مشاهده'
                : (! $data['selected_child'] ? 'دانش‌آموزی به حساب متصل نشده است' : 'هنوز نمره‌ای ثبت نشده'),
            'open_homework' => $data['pending_homework_count'] !== null ? (int) $data['pending_homework_count'] : '—',
            'open_homework_note' => $data['pending_homework_count'] !== null
                ? 'تکلیف بدون ارسال'
                : (! $data['selected_child']
                    ? 'دانش‌آموزی به حساب متصل نشده است'
                    : 'مجوز مشاهده تکالیف فعال نیست'),
            'next_meeting' => $data['meetings']->firstWhere('status', 'scheduled')?->starts_at
                ? JalaliDate::format($data['meetings']->firstWhere('status', 'scheduled')->starts_at, true)
                : '—',
            'next_meeting_note' => $data['meetings']->firstWhere('status', 'scheduled')
                ? 'جلسه ثبت‌شده'
                : 'جلسه‌ای زمان‌بندی نشده است',
        ];

        $data['subjects'] = $data['results']->filter(fn ($result) => $result->exam?->course)
            ->groupBy(fn ($result) => $result->exam->course->title)
            ->map(fn ($items, $name) => [
                'name' => $name,
                'score' => round($items->avg(fn ($item) => $item->max_score > 0 ? $item->score / $item->max_score * 20 : 0), 1),
                'class_avg' => null,
            ])->values();

        $trend = $data['result_trend'];
        $currentJalali = JalaliDate::fromGregorian(now());
        $periods = collect(range(5, 0))->map(
            fn ($offset) => JalaliDate::monthKeyByOffset($currentJalali['year'], $currentJalali['month'], $offset)
        );
        $data['months'] = $periods->map(function (string $month): string {
            [$year, $monthNumber] = array_map('intval', explode('-', $month));
            return JalaliDate::monthLabel($year, $monthNumber);
        })->values();
        $data['scores'] = $periods->map(function ($month) use ($trend) {
            return $trend->get($month);
        })->values();
        $data['resultDates'] = $data['results']->mapWithKeys(fn ($result) => [
            $result->id => $result->created_at ? JalaliDate::format($result->created_at) : '—',
        ])->all();

        $data['classAvg'] = [];
        $homeworkTrend = $data['homework_trend'];
        $data['homeworkMonths'] = $data['months'];
        $data['hwOnTime'] = $periods->map(fn ($month) => $homeworkTrend->has($month)
            ? (int) data_get($homeworkTrend->get($month), 'on_time', 0)
            : null)->values();
        $data['hwLate'] = $periods->map(fn ($month) => $homeworkTrend->has($month)
            ? (int) data_get($homeworkTrend->get($month), 'late', 0)
            : null)->values();
        $data['hwMissing'] = $periods->map(fn ($month) => $homeworkTrend->has($month)
            ? (int) data_get($homeworkTrend->get($month), 'missing', 0)
            : null)->values();
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
        $filters = $request->validate([
            'month' => ['nullable', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/'],
        ]);
        $currentJalali = JalaliDate::fromGregorian(now());
        $monthKey = $filters['month'] ?? JalaliDate::monthKey($currentJalali['year'], $currentJalali['month']);
        [$year, $month] = array_map('intval', explode('-', $monthKey));
        abort_if($year < 1300 || $year > 1500, 422, 'ماه انتخاب‌شده معتبر نیست.');

        $data = $this->build($request, $dashboard, $monthKey);
        $start = JalaliDate::toGregorian($year, $month, 1);
        $monthDays = JalaliDate::daysInMonth($year, $month);
        $todayJalali = JalaliDate::fromGregorian(now());
        $isCurrentMonth = $year === $todayJalali['year'] && $month === $todayJalali['month'];
        $todayJalaliDay = $isCurrentMonth ? $todayJalali['day'] : 0;

        $records = $data['attendance_calendar']->groupBy(
            fn ($record) => JalaliDate::fromGregorian($record->session_date)['day']
        );
        $days = collect(range(1, $monthDays))->map(function (int $day) use ($records, $todayJalaliDay): array {
            $dayRecords = $records->get($day, collect());
            $statuses = $dayRecords->pluck('status');
            $status = $statuses->contains('absent') ? 'a'
                : ($statuses->contains('late') ? 'l'
                : ($statuses->contains('present') ? 'p' : 'future'));
            return ['day' => $day, 'status' => $status, 'today' => $todayJalaliDay > 0 && $day === $todayJalaliDay];
        });

        $monthBreakdown = $data['attendance_calendar']->groupBy('status')->map->count();
        $monthTotal = (int) $monthBreakdown->get('present', 0) + (int) $monthBreakdown->get('late', 0) + (int) $monthBreakdown->get('absent', 0);
        $monthAttendance = [
            'present' => (int) $monthBreakdown->get('present', 0),
            'late' => (int) $monthBreakdown->get('late', 0),
            'absent' => (int) $monthBreakdown->get('absent', 0),
            'percent' => $monthTotal > 0 ? (int) round((($monthBreakdown->get('present', 0) + $monthBreakdown->get('late', 0)) / $monthTotal) * 100) : null,
        ];

        $monthOptions = collect(range(0, 11))->map(function (int $offset) use ($currentJalali): array {
            $value = JalaliDate::monthKeyByOffset($currentJalali['year'], $currentJalali['month'], $offset);
            [$optionYear, $optionMonth] = array_map('intval', explode('-', $value));
            return ['value' => $value, 'label' => JalaliDate::monthLabel($optionYear, $optionMonth)];
        });

        return view('parent.attendance', [
            'kid' => $data['selected_child'], 'kids' => $data['children'],
            'attendance' => $monthAttendance,
            'calendar' => $days, 'monthOffset' => ($start->dayOfWeek + 1) % 7,
            'monthTitle' => JalaliDate::monthLabel($year, $month),
            'monthKey' => $monthKey, 'monthOptions' => $monthOptions,
            'absencePattern' => (int) $monthBreakdown->get('absent', 0).' غیبت در این ماه',
            'lastLate' => $data['last_late_attendance']?->session_date
                ? JalaliDate::format($data['last_late_attendance']->session_date)
                : 'موردی ثبت نشده',
        ]);
    }

    public function exams(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);
        $results = $data['exam_results'];
        $resultDates = $results->getCollection()->mapWithKeys(fn ($result) => [
            $result->id => $result->created_at ? JalaliDate::format($result->created_at) : '—',
        ])->all();
        $subjects = $results->getCollection()->filter(fn ($result) => $result->exam?->course)
            ->groupBy(fn ($result) => $result->exam->course->title)
            ->map(fn ($items, $name) => [
                'name' => $name,
                'score' => round($items->avg(fn ($item) => $item->max_score > 0 ? $item->score / $item->max_score * 20 : 0), 1),
                'class_avg' => null,
            ])->values();

        return view('parent.exams', [
            'kid' => $data['selected_child'], 'kids' => $data['children'],
            'subjects' => $subjects, 'results' => $results, 'resultDates' => $resultDates,
        ]);
    }

    public function homework(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);
        $homeworkPaginator = $data['homeworks'];
        $homework = $homeworkPaginator->getCollection()->map(function ($item): array {
            $submission = $item->submissions->first();
            $late = $submission && $item->due_at && $submission->submitted_at && $submission->submitted_at->gt($item->due_at);
            $missing = ! $submission && $item->due_at && $item->due_at->isPast();
            return [
                'title' => $item->title,
                'due' => $item->due_at ? JalaliDate::format($item->due_at, true) : 'بدون مهلت',
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
            'homework' => $homework, 'homeworkPaginator' => $homeworkPaginator, 'hwTotals' => $totals,
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

    private function build(Request $request, GuardianDashboardService $dashboard, ?string $attendanceMonthKey = null): array
    {
        $validated = $request->validate(['child' => ['nullable', 'integer', 'min:1']]);
        return $dashboard->build(
            $request->user(),
            isset($validated['child']) ? (int) $validated['child'] : null,
            $attendanceMonthKey
        );
    }

    private function attendanceSummary(array $data): array
    {
        $breakdown = $data['attendance_breakdown'];
        return [
            'present' => (int) $breakdown->get('present', 0),
            'late' => (int) $breakdown->get('late', 0),
            'absent' => (int) $breakdown->get('absent', 0),
            'percent' => $data['attendance_rate'],
        ];
    }
}
