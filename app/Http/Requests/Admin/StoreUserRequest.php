<?php

namespace App\Http\Requests\Admin;

use App\Models\Classroom;
use App\Models\User;
use App\Services\Admin\TeacherAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    private const ROLES = ['student', 'teacher', 'parent'];

    protected function prepareForValidation(): void
    {
        $digits = strtr((string) $this->input('phone', ''), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if ($this->route('role') === 'teacher') {
            $this->merge([
                'phone' => preg_replace('/[\\s-]+/u', '', trim($digits)) ?? trim($digits),
                'teacher_access' => $this->input('teacher_access', []),
                'course_ids' => $this->input('course_ids', []),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('users.manage') ?? false;
    }

    public function rules(): array
    {
        $role = (string) $this->route('role');
        $isTeacher = $role === 'teacher';

        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => [
                $isTeacher ? 'nullable' : 'required',
                'email:rfc',
                'max:190',
                Rule::unique('users', 'email'),
            ],
            'phone' => array_values(array_filter([
                $isTeacher ? 'required' : 'nullable',
                'string',
                'max:30',
                $isTeacher ? Rule::unique('users', 'phone') : null,
            ])),
            'external_student_id' => [
                $role === 'student' ? 'nullable' : 'prohibited',
                'string',
                'max:120',
                Rule::unique('users', 'external_student_id'),
            ],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'grade' => [$role === 'student' ? 'required' : 'nullable', 'string', 'max:50'],
            'subject' => [$isTeacher ? 'required' : 'prohibited', 'string', 'max:100'],
            'profile_note' => ['nullable', 'string', 'max:5000'],
            'status' => [$isTeacher ? 'required' : 'prohibited', Rule::in(['active', 'pending'])],
            'classroom_ids' => [$role === 'student' ? 'nullable' : 'prohibited', 'array', 'max:100'],
            'classroom_ids.*' => ['integer', 'distinct', 'exists:classrooms,id'],
            'child_ids' => [$role === 'parent' ? 'nullable' : 'prohibited', 'array', 'max:30'],
            'child_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'course_ids' => [$isTeacher ? 'nullable' : 'prohibited', 'array', 'max:100'],
            'course_ids.*' => ['integer', 'distinct', 'exists:courses,id'],
            'teacher_access' => [$isTeacher ? 'nullable' : 'prohibited', 'array', 'max:'.count(TeacherAccessService::ACCESS_GROUPS)],
            'teacher_access.*' => ['string', 'distinct', Rule::in(array_keys(TeacherAccessService::ACCESS_GROUPS))],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $role = (string) $this->route('role');

            if (! in_array($role, self::ROLES, true)) {
                $validator->errors()->add('role', 'نقش کاربری انتخاب‌شده معتبر نیست.');
                return;
            }

            if ($role === 'student' && $this->filled('classroom_ids')) {
                $ids = $this->input('classroom_ids', []);
                $valid = Classroom::whereIn('id', $ids)->where('status', 'active')->count();
                if ($valid !== count($ids)) {
                    $validator->errors()->add('classroom_ids', 'همه کلاس‌های انتخاب‌شده فعال نیستند.');
                }
            }

            if ($role === 'parent' && $this->filled('child_ids')) {
                $ids = $this->input('child_ids', []);
                $valid = User::whereIn('id', $ids)
                    ->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->where('name', 'student'))->count();
                if ($valid !== count($ids)) {
                    $validator->errors()->add('child_ids', 'فقط حساب‌های دارای نقش دانش‌آموز قابل اتصال هستند.');
                }
            }
        }];
    }
}
