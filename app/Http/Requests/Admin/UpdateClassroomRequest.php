<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('classes.manage') ?? false; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('teacher_id') && ! User::whereKey($this->integer('teacher_id'))
                ->where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))->exists()) {
                $validator->errors()->add('teacher_id', 'مدرس انتخاب‌شده فعال یا دارای نقش مدرس نیست.');
            }
        }];
    }
}
