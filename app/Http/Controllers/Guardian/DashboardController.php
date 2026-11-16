<?php

namespace App\\Http\\Controllers\\Guardian;

use App\\Http\\Controllers\\Controller;
use App\\Services\\GuardianDashboardService;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Carbon;
use Illuminate\\View\\View;

final class DashboardController extends Controller
{
    public function index(Request $request, GuardianDashboardService $dashboard): View
    {
        $validated = $request->validate([
            'child' => ['nullable', 'integer', 'min:1'],
        ]);

        $data = $dashboard->build(
            $request->user(),
            isset($validated['child']) ? (int) $validated['child'] : null
        );

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
        $data['upcomingExams'] = collect();
        $data['teacherNotes'] = $data['reviewed_homework']->filter(fn ($submission) => filled($submission->feedback))
            ->map(fn ($submission) => [
                'teacher' => $submission->reviewer?->name ?? 'مدرس',
                'text' => $submission->feedback,
                'type' => 'feedback',
            ]);

        return view('parent.dashboard', $data);
    }
}
