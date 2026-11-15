<?php

namespace App\Http\Requests\Admin;

use App\Models\Classroom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $digits = strtr((string) $this->input('phone', ''), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        $this->merge(['phone' => preg_replace('/[\\s-]+/u', '', trim($digits)) ?? trim($digits)]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('users.manage') ?? false;
    }

    public function rules(): array
    {
        $student = $this->route('student');
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($student->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'external_student_id' => ['nullable', 'string', 'max:120', Rule::unique('users', 'external_student_id')->ignore($student->id)],
            'grade' => ['required', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'classroom_ids' => ['nullable', 'array', 'max:100'],
            'classroom_ids.*' => ['integer', 'distinct', 'exists:classrooms,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $student = $this->route('student');
            if (! $student->hasRole('student') || $student->hasRole('admin')) {
                $validator->errors()->add('student', 'حساب انتخاب‌شده دانش‌آموز قابل ویرایش نیست.');
            }
            $ids = $this->input('classroom_ids', []);
            if ($ids !== [] && Classroom::query()->whereIn('id', $ids)->where('status', 'active')->count() !== count($ids)) {
                $validator->errors()->add('classroom_ids', 'همه کلاس‌های انتخاب‌شده فعال نیستند.');
            }
        }];
    }
}
