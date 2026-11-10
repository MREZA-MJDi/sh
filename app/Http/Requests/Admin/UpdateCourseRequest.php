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
            'price' => ['nullable', 'required_if:type,paid', 'integer', 'min:1', 'max:1000000000'],
            'delivery_type' => ['required', Rule::in(['spotplayer', 'skyroom', 'onsite'])],
            'spotplayer_course_id' => ['nullable', 'string', 'max:190', 'prohibited_unless:delivery_type,spotplayer'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $id = $this->input('teacher_id');
            if ($id && ! User::whereKey($id)->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))->exists()) {
                $validator->errors()->add('teacher_id', 'کاربر انتخاب‌شده نقش مدرس ندارد.');
            }
        }];
    }
}
