<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('courses.manage') ?? false; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'subject' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(['free', 'paid'])],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'delivery_type' => ['required', Rule::in(['spotplayer', 'skyroom', 'onsite'])],
            'spotplayer_course_id' => ['nullable', 'string', 'max:190', 'prohibited_unless:delivery_type,spotplayer'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $teacherId = $this->input('teacher_id');

            if ($teacherId && ! User::whereKey($teacherId)->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->exists()) {
                $validator->errors()->add('teacher_id', 'مدرس انتخاب‌شده فعال یا دارای نقش مدرس نیست.');
            }

            if ($this->input('type') === 'paid' && (int) $this->input('price', 0) < 1) {
                $validator->errors()->add('price', 'قیمت دوره پولی باید بیشتر از صفر باشد.');
            }
        }];
    }
}
