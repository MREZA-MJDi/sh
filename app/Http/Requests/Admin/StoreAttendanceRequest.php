<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassSession;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
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
                ->with('students')
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

            $allowed = $classroom->students->modelKeys();
            foreach ((array) $this->input('attendance', []) as $studentId => $entry) {
                if (! in_array((int) $studentId, $allowed, true)) {
                    $validator->errors()->add('attendance', 'همه افراد انتخاب‌شده عضو این کلاس نیستند.');
                    continue;
                }

                $status = $entry['status'] ?? null;
                $joinedAt = filled($entry['joined_at'] ?? null) ? Carbon::parse($entry['joined_at']) : null;
                $attendedMinutes = filled($entry['attended_minutes'] ?? null) ? (int) $entry['attended_minutes'] : null;

                if ($status === 'late' && ! $joinedAt) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'برای ثبت تأخیر، زمان واقعی ورود را وارد کنید.');
                }

                if ($status === 'late' && $joinedAt && $joinedAt->lessThanOrEqualTo($session->starts_at)) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'زمان ورود ثبت‌شده تأخیر را نشان نمی‌دهد.');
                }

                if ($status === 'present' && $joinedAt && $joinedAt->greaterThan($session->starts_at)) {
                    $validator->errors()->add("attendance.{$studentId}.status", 'با توجه به زمان ورود، وضعیت باید «با تأخیر» باشد.');
                }

                if ($joinedAt && $session->ends_at && $joinedAt->greaterThan($session->ends_at)) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'زمان ورود نمی‌تواند بعد از پایان جلسه باشد.');
                }

                if ($status === 'absent' && ($joinedAt || ($attendedMinutes ?? 0) > 0)) {
                    $validator->errors()->add("attendance.{$studentId}.status", 'برای دانش‌آموز غایب، زمان ورود یا مدت حضور ثبت نکنید.');
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