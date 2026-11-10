<?php

namespace App\Http\Requests\Admin;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    private const ROLES = ['student', 'teacher', 'parent'];

    public function authorize(): bool { return $this->user()?->hasPermissionTo('users.manage') ?? false; }

    public function rules(): array
    {
        $role = (string) $this->route('role');

        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'external_student_id' => [
                $role === 'student' ? 'nullable' : 'prohibited',
                'string',
                'max:120',
                Rule::unique('users', 'external_student_id'),
            ],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'grade' => [$role === 'student' ? 'required' : 'nullable', 'string', 'max:50'],
            'subject' => [$role === 'teacher' ? 'required' : 'nullable', 'string', 'max:100'],
            'classroom_ids' => [$role === 'student' ? 'nullable' : 'prohibited', 'array', 'max:100'],
            'classroom_ids.*' => ['integer', 'distinct', 'exists:classrooms,id'],
            'child_ids' => [$role === 'parent' ? 'nullable' : 'prohibited', 'array', 'max:30'],
            'child_ids.*' => ['integer', 'distinct', 'exists:users,id'],
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
                    ->whereHas('roles', fn ($query) => $query->where('name', 'student'))->count();
                if ($valid !== count($ids)) {
                    $validator->errors()->add('child_ids', 'فقط حساب‌های دارای نقش دانش‌آموز قابل اتصال هستند.');
                }
            }
        }];
    }
}
