<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Services\GuardianDashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            'next_meeting' => optional($data['meetings']->firstWhere('status', 'scheduled')?->starts_at)->format('m/d') ?? '—',
            'next_meeting_note' => 'جلسه ثبت‌شده',
        ];

        $data['subjects'] = $data['results']->filter(fn ($result) => $result->exam?->course)
            ->groupBy(fn ($result) => $result->exam->course->title)
            ->map(fn ($items, $name) => [
                'name' => $name,
                'score' => round($items->avg(fn ($item) => $item->max_score > 0 ? $item->score / $item->max_score * 20 : 0), 1),
                'class_avg' => null,
            ])->values();

        $trend = $data['result_trend']->groupBy(fn ($result) => $result->created_at->format('Y-m'));
        $data['months'] = $trend->keys()->map(fn ($month) => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M'))->values();
        $data['scores'] = $trend->map(fn ($items) => round($items->avg(fn ($item) => $item->max_score > 0 ? $item->score / $item->max_score * 20 : 0), 1))->values();
        $data['classAvg'] = [];
        $data['hwOnTime'] = $data['homework_trend']->pluck('on_time')->values();
        $data['hwLate'] = $data['homework_trend']->pluck('late')->values();
        $data['hwMissing'] = $data['homework_trend']->pluck('missing')->values();
        $data['teacherNotes'] = $data['reviewed_homework']->filter(fn ($submission) => filled($submission->feedback))
            ->map(fn ($submission) => [
                'teacher' => $submission->reviewer?->name ?? 'مدرس',
                'text' => $submission->feedback,
                'type' => 'feedback',
            ]);

        return view('parent.dashboard', $data);
    }

    public function attendance(Request $request, GuardianDashboardService $dashboard): View
    {
        $data = $this->build($request, $dashboard);
        $records = $data['attendance_calendar']->keyBy(fn ($record) => Carbon::parse($record->session_date)->day);
        $start = now()->startOfMonth();
        $days = collect(range(1, now()->daysInMonth))->map(function (int $day) use ($records): array {
            $record = $records->get($day);
            $status = match ($record?->status) {
                'present' => 'p', 'absent' => 'a', 'late' => 'l', default => 'future',
            };
            return ['day' => $day, 'status' => $status, 'today' => $day === now()->day];
        });

        return view('parent.attendance', [
            'kid' => $data['selected_child'], 'kids' => $data['children'],
            'attendance' => $this->attendanceSummary($data),
            'calendar' => $days, 'monthOffset' => ($start->dayOfWeek + 1) % 7,
            'monthTitle' => $start->translatedFormat('F Y'),
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
