<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
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
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1', 'max:300'],
            'attendance.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
            'attendance.*.joined_at' => ['nullable', 'date_format:H:i'],
            'attendance.*.left_at' => ['nullable', 'date_format:H:i'],
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

            $allowed = $classroom->students->modelKeys();
            foreach ((array) $this->input('attendance', []) as $studentId => $entry) {
                $joined = $entry['joined_at'] ?? null;
                $left = $entry['left_at'] ?? null;
                $status = $entry['status'] ?? null;
                if ($left && ! $joined) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'برای ثبت زمان خروج، زمان ورود نیز الزامی است.');
                } elseif ($joined && $left && $left <= $joined) {
                    $validator->errors()->add("attendance.{$studentId}.left_at", 'زمان خروج باید بعد از زمان ورود باشد.');
                }
                if ($status === 'late' && ! $joined) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'برای ثبت تأخیر، زمان ورود الزامی است.');
                }
                if (in_array($status, ['absent', 'excused'], true) && ($joined || $left)) {
                    $validator->errors()->add("attendance.{$studentId}.joined_at", 'برای غایب یا غیبت موجه، زمان ورود و خروج ثبت نکنید.');
                }
            }
            foreach (array_keys((array) $this->input('attendance', [])) as $studentId) {
                if (! in_array((int) $studentId, $allowed, true)) {
                    $validator->errors()->add('attendance', 'همه افراد انتخاب‌شده عضو این کلاس نیستند.');
                    break;
                }
            }
        }];
    }
}
