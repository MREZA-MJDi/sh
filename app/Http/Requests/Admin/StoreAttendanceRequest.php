<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassSession;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

class StoreAttendanceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $classroomId = $this->input('classroom_id', $this->input('class_id'));
        $sessionId = $this->input('class_session_id', $this->input('session'));
        $attendance = $this->input('attendance');

        // Keep compatibility with the existing attendance screen and its
        // attendance.js radio groups: status[student_id] + note[student_id].
        if (! is_array($attendance)) {
            $attendance = [];
            $statuses = (array) $this->input('status', []);
            $notes = (array) $this->input('note', []);

            foreach ($statuses as $studentId => $status) {
                $attendance[$studentId] = [
                    'status' => $status,
                    'note' => $notes[$studentId] ?? null,
                ];
            }
        }

        $this->merge([
            'classroom_id' => $classroomId,
            'class_session_id' => $sessionId,
            'attendance' => $attendance,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['attendance.manage.all', 'attendance.manage.assigned']) ?? false;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'class_session_id' => ['required', 'integer', 'exists:class_sessions,id'],
            'attendance' => ['required', 'array', 'min:1', 'max:300'],
            'attendance.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
            'attendance.*.joined_at' => ['nullable', 'date'],
            'attendance.*.attended_minutes' => ['nullable', 'integer', 'between:0,1440'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $workspace = app(ClassroomWorkspaceService::class);
            $classroom = $workspace->queryFor($this->user())
                ->find($this->integer('classroom_id'));

            if (! $classroom) {
                $validator->errors()->add('classroom_id', 'این کلاس در فهرست کلاس‌های مجاز شما نیست.');
                return;
            }

            $session = ClassSession::query()
                ->whereKey($this->integer('class_session_id'))
                ->where('classroom_id', $classroom->id)
                ->where('status', '!=', 'cancelled')
                ->first();

            if (! $session) {
                $validator->errors()->add('class_session_id', 'جلسه انتخاب‌شده متعلق به این کلاس نیست یا لغو شده است.');
                return;
            }

            if ($session->starts_at->isFuture()) {
                $validator->errors()->add('class_session_id', 'برای جلسه‌ای که هنوز شروع نشده حضور و غیاب ثبت نکنید.');
            }

            $submittedAttendance = (array) $this->input('attendance', []);
            $submittedStudentIds = collect(array_keys($submittedAttendance))
                ->map(fn ($studentId) => (int) $studentId)
                ->unique()
                ->values();
            $allowedStudentIds = $classroom->students()
                ->whereKey($submittedStudentIds->all())
                ->pluck('users.id')
                ->map(fn ($studentId) => (int) $studentId)
                ->all();
            $allowed = array_fill_keys($allowedStudentIds, true);

            foreach ($submittedAttendance as $studentId => $entry) {
                if (! preg_match('/^[1-9]\\d*$/', (string) $studentId) || ! isset($allowed[(int) $studentId])) {
                    $validator->errors()->add('attendance', 'شناسه دانش‌آموز معتبر نیست یا به این کلاس تعلق ندارد.');
                    continue;
                }

                $status = $entry['status'] ?? null;
                $joinedAt = null;
                if (filled($entry['joined_at'] ?? null)) {
                    try {
                        $joinedAt = Carbon::parse($entry['joined_at']);
                    } catch (Throwable) {
                        continue; // The date rule reports malformed values.
                    }
                }

                $attendedMinutes = filled($entry['attended_minutes'] ?? null)
                    ? (int) $entry['attended_minutes']
                    : null;

                // The unchanged radio design records status and notes only.
                // Do not fabricate a check-in timestamp for a "late" status.
                if ($status === 'late' && $joinedAt && $joinedAt->lessThanOrEqualTo($session->starts_at)) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'زمان ورود ثبت‌شده تأخیر را نشان نمی‌دهد.');
                }

                if ($status === 'present' && $joinedAt && $joinedAt->greaterThan($session->starts_at)) {
                    $validator->errors()->add("attendance.{$studentId}.status", 'با توجه به زمان ورود، وضعیت باید «با تأخیر» باشد.');
                }

                if ($joinedAt && $session->ends_at && $joinedAt->greaterThan($session->ends_at)) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'زمان ورود نمی‌تواند بعد از پایان جلسه باشد.');
                }

                if ($session->ends_at && $attendedMinutes !== null) {
                    $scheduled = max(0, intdiv($session->ends_at->getTimestamp() - $session->starts_at->getTimestamp(), 60));
                    if ($attendedMinutes > $scheduled) {
                        $validator->errors()->add("attendance.{$studentId}.attended_minutes", 'مدت حضور از مدت زمان این جلسه بیشتر است.');
                    }
                }
            }
        }];
    }
}