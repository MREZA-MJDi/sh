<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\User;
use App\Notifications\AttendanceAbsenceNotification;
use App\Services\Admin\AttendanceService;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Support\JalaliDate;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $classrooms = $workspace->queryFor($request->user())
            ->with('course:id,title')
            ->where('status', '!=', 'archived')
            ->orderBy('name')
            ->get();

        $classes = $classrooms->mapWithKeys(fn ($item) => [
            $item->id => trim($item->name.($item->course?->title ? ' — '.$item->course->title : '')),
        ]);

        $selectedClassroomId = (int) ($request->query('class_id') ?: $request->query('classroom_id') ?: 0);
        $classroom = $selectedClassroomId
            ? $classrooms->firstWhere('id', $selectedClassroomId)
            : $classrooms->first();

        abort_if($selectedClassroomId && ! $classroom, 404);

        $selectedDate = $this->resolveDate($request->query('date'));
        $date = $this->formatJalaliDate($selectedDate);

        $sessions = $classroom
            ? ClassSession::query()
                ->where('classroom_id', $classroom->id)
                ->whereDate('starts_at', $selectedDate->toDateString())
                ->where('status', '!=', 'cancelled')
                ->orderBy('starts_at')
                ->get()
            : collect();

        $selectedSessionId = (int) ($request->query('session') ?: $request->query('class_session_id') ?: 0);
        $session = $selectedSessionId
            ? $sessions->firstWhere('id', $selectedSessionId)
            : $sessions->first();

        if (! $session && $sessions->isNotEmpty()) {
            $session = $sessions->first();
        }

        $students = $classroom
            ? $classroom->students()->orderBy('name')->get()
            : collect();

        $records = $session
            ? Attendance::query()->where('class_session_id', $session->id)->get()->keyBy('student_id')
            : collect();

        $roster = $students->map(function (User $student) use ($records): array {
            $record = $records->get($student->id);

            return [
                'id' => $student->id,
                'name' => $student->name,
                'status' => $record?->status,
                'note' => $record?->note ?? '',
            ];
        });

        $report = collect();
        $classroomIds = $classrooms->modelKeys();

        if ($classroomIds !== []) {
            $report = DB::table('attendances as attendance')
                ->join('class_sessions as sessions', 'sessions.id', '=', 'attendance.class_session_id')
                ->join('classrooms', 'classrooms.id', '=', 'attendance.classroom_id')
                ->join('users as students', 'students.id', '=', 'attendance.student_id')
                ->whereIn('attendance.classroom_id', $classroomIds)
                ->where('sessions.status', '!=', 'cancelled')
                ->select([
                    'students.id as student_id',
                    'classrooms.id as classroom_id',
                    'students.name as student_name',
                    'classrooms.name as classroom_name',
                    DB::raw('COUNT(DISTINCT attendance.class_session_id) as total_sessions'),
                    DB::raw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) as absence_count"),
                ])
                ->groupBy('students.id', 'classrooms.id', 'students.name', 'classrooms.name')
                ->havingRaw("SUM(CASE WHEN attendance.status = 'absent' THEN 1 ELSE 0 END) > 0")
                ->orderByDesc('absence_count')
                ->orderBy('students.name')
                ->get()
                ->map(fn ($row) => [
                    (int) $row->student_id,
                    (int) $row->classroom_id,
                    $row->student_name,
                    $row->classroom_name,
                    (int) $row->total_sessions,
                    (int) $row->absence_count,
                ]);
        }

        return view('admin.attendance.index', compact(
            'classes',
            'classrooms',
            'classroom',
            'date',
            'sessions',
            'session',
            'roster',
            'report'
        ));
    }

    public function store(
        StoreAttendanceRequest $request,
        AttendanceService $attendance,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $data = $request->validated();
        $classroom = $workspace->queryFor($request->user())->findOrFail($data['classroom_id']);
        $session = ClassSession::query()
            ->where('classroom_id', $classroom->id)
            ->findOrFail($data['class_session_id']);

        $attendance->record($classroom, $session, $data['attendance'], (int) $request->user()->id);\n        app(AttendanceGuardianNotifier::class)->notifyThresholds($classroom, $data['attendance']);

        return redirect()->route('admin.attendance.index', [
            'class_id' => $classroom->id,
            'date' => $this->formatJalaliDate($session->starts_at),
            'session' => $session->id,
        ])->with('success', 'حضور و غیاب این جلسه با موفقیت ذخیره شد.');
    }

    public function notifyAbsence(
        Request $request,
        int $classroom,
        int $student,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $class = $workspace->queryFor($request->user())->findOrFail($classroom);
        $studentModel = $class->students()->whereKey($student)->firstOrFail();

        $absenceCount = Attendance::query()
            ->where('classroom_id', $class->id)
            ->where('student_id', $studentModel->id)
            ->where('status', 'absent')
            ->count();

        abort_unless($absenceCount >= 3, 404);

        $guardians = $studentModel->guardians()
            ->where('users.is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'parent'))
            ->get();

        if ($guardians->isEmpty()) {
            return back()->with('warning', 'برای این دانش‌آموز ولی فعال ثبت نشده است.');
        }

        Notification::send(
            $guardians,
            new AttendanceAbsenceNotification($studentModel, $class->name, $absenceCount)
        );

        return back()->with('success', 'گزارش غیبت برای ولی دانش‌آموز ارسال شد.');
    }

    private function resolveDate(?string $value): CarbonImmutable
    {
        if (blank($value)) {
            return CarbonImmutable::now(config('app.timezone', 'Asia/Tehran'))->startOfDay();
        }

        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $value = str_replace(['٬', '،', ','], '', $value);

        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $value, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            try {
                return $year < 1700
                    ? JalaliDate::toGregorian($year, $month, $day)
                    : CarbonImmutable::create($year, $month, $day, 0, 0, 0, config('app.timezone', 'Asia/Tehran'));
            } catch (\Throwable) {
                abort(422, 'تاریخ واردشده معتبر نیست.');
            }
        }

        try {
            return CarbonImmutable::parse($value, config('app.timezone', 'Asia/Tehran'))->startOfDay();
        } catch (\Throwable) {
            abort(422, 'تاریخ واردشده معتبر نیست.');
        }
    }

    private function formatJalaliDate($date): string
    {
        $parts = JalaliDate::fromGregorian($date);

        return strtr(sprintf('%04d/%02d/%02d', $parts['year'], $parts['month'], $parts['day']), [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);
    }
}