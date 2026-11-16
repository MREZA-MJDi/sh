<?php

namespace App\\Http\\Controllers\\Teacher;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Attendance;
use App\\Models\\Consultation;
use App\\Models\\HomeworkSubmission;
use App\\Services\\Admin\\ClassroomWorkspaceService;
use App\\Services\\Admin\\DashboardService;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Http\\Request;
use Illuminate\\View\\View;

final class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardService $dashboard,
        ClassroomWorkspaceService $workspace
    ): View {
        $user = $request->user();
        $data = $dashboard->build($user);
        $classroomIds = $workspace->queryFor($user)->where('status', 'active')->select('classrooms.id');

        $todayClasses = $data['todayClasses']->map(fn ($classroom) => [
            'title' => $classroom->name,
            'time' => $classroom->course?->title ?? 'کلاس فعال',
            'status' => filled($classroom->meeting_url) ? 'link' : 'scheduled',
        ]);

        $pending = HomeworkSubmission::query()
            ->where('status', 'submitted')
            ->whereHas('homework', fn (Builder $homework) => $homework->whereIn('classroom_id', clone $classroomIds))
            ->with(['homework:id,title,classroom_id', 'homework.classroom.students'])
            ->latest('submitted_at')
            ->limit(8)
            ->get()
            ->groupBy('homework_id')
            ->map(fn ($items) => [
                'title' => $items->first()->homework?->title ?? 'تکلیف',
                'submitted' => $items->count(),
                'total' => $items->first()->homework?->classroom?->students?->count() ?? 0,
                'type' => 'در انتظار بررسی',
            ])->values();

        $weekAttendance = $data['weeklyAttendance']->map(fn ($day) => [
            'label' => $day['label'],
            'percent' => $day['rate'],
        ]);

        $newMessages = $data['recentMessages']->map(fn ($message) => [
            'from' => $message->sender?->name ?? 'کاربر',
            'text' => $message->subject ?: $message->body,
        ]);

        $data['teacher'] = $user;
        $data['stats'] = [
            'classes_today' => $todayClasses->count(),
            'classes_today_note' => 'کلاس‌های تحت مدیریت شما',
            'homework_review' => (int) $data['stats']['pending_homework'],
            'homework_review_note' => 'ارسال در انتظار بررسی',
            'absent_today' => Attendance::query()
                ->whereIn('classroom_id', clone $classroomIds)
                ->whereDate('session_date', today())
                ->where('status', 'absent')
                ->count(),
            'absent_today_note' => 'در کلاس‌های شما',
            'new_messages' => $newMessages->count(),
            'new_messages_note' => 'پیام‌های باز',
        ];
        $data['todayClasses'] = $todayClasses;
        $data['weekAttendance'] = $weekAttendance;
        $data['pendingHomework'] = $pending;
        $data['newMessages'] = $newMessages;
        $data['liveClass'] = null;
        $data['myConsultations'] = Consultation::query()->where('requester_id', $user->id)->latest()->limit(5)->get();

        return view('teacher.dashboard', $data);
    }
}
