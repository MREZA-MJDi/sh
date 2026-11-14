<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Services\Admin\TeacherAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTeacherAccessRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $digits = strtr((string) $this->input('phone', ''), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge([
            'phone' => preg_replace('/[\\s-]+/u', '', trim($digits)) ?? trim($digits),
            'teacher_access' => $this->input('teacher_access', []),
            'course_ids' => $this->input('course_ids', []),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('users.manage') ?? false;
    }

    public function rules(): array
    {
        /** @var User|null $teacher */
        $teacher = $this->route('teacher');

        return [
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($teacher?->id)],
            'email' => ['nullable', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($teacher?->id)],
            'subject' => ['required', 'string', 'max:100'],
            'profile_note' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['active', 'pending'])],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'course_ids' => ['nullable', 'array', 'max:100'],
            'course_ids.*' => ['integer', 'distinct', 'exists:courses,id'],
            'teacher_access' => ['nullable', 'array', 'max:'.count(TeacherAccessService::ACCESS_GROUPS)],
            'teacher_access.*' => ['string', 'distinct', Rule::in(array_keys(TeacherAccessService::ACCESS_GROUPS))],
        ];
    }
}
