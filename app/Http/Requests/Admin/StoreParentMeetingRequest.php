<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreParentMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('meetings.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'guardian_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'title' => ['required', 'string', 'max:180'],
            'agenda' => ['nullable', 'string', 'max:5000'],
            'meeting_type' => ['required', Rule::in(['in_person', 'phone', 'online'])],
            'meeting_url' => ['nullable', 'url:https', 'max:2048'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:scheduled_at'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $guardianId = $this->integer('guardian_id');
            $studentId = $this->integer('student_id');
            $linked = \App\Models\User::query()->whereKey($guardianId)
                ->whereHas('roles', fn ($q) => $q->where('name', 'parent'))
                ->whereHas('children', fn ($q) => $q->whereKey($studentId)->whereHas('roles', fn ($roles) => $roles->where('name', 'student')))
                ->exists();

            if (! $linked) {
                $validator->errors()->add('student_id', 'دانش‌آموز انتخاب‌شده به ولی انتخابی متصل نیست.');
            }

            if ($this->filled('classroom_id') && ! \App\Models\Classroom::query()
                ->whereKey($this->integer('classroom_id'))
                ->whereHas('students', fn ($q) => $q->whereKey($studentId))
                ->exists()) {
                $validator->errors()->add('classroom_id', 'کلاس انتخاب‌شده متعلق به این دانش‌آموز نیست.');
            }
        }];
    }
}