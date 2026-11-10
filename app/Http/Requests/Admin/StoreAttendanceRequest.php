<?php

namespace App\Http\Requests\Admin;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasAnyPermission(['attendance.manage.all', 'attendance.manage.assigned']) ?? false; }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'session_date' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1', 'max:300'],
            'attendance.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused'])],
            'attendance.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $classroom = Classroom::with('students')->find($this->integer('classroom_id'));
            if (! $classroom) return;

            if ($this->user()?->hasRole('teacher') && ! $this->user()?->hasRole('admin')
                && $classroom->teacher_id !== $this->user()->id) {
                $validator->errors()->add('classroom_id', 'این کلاس در فهرست کلاس‌های شما نیست.');
                return;
            }

            $allowed = $classroom->students->modelKeys();
            foreach (array_keys((array) $this->input('attendance', [])) as $studentId) {
                if (! in_array((int) $studentId, $allowed, true)) {
                    $validator->errors()->add('attendance', 'همه افراد انتخاب‌شده عضو این کلاس نیستند.');
                    break;
                }
            }
        }];
    }
}
