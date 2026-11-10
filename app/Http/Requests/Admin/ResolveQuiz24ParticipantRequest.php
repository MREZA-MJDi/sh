<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResolveQuiz24ParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('integrations.manage') ?? false;
    }

    public function rules(): array
    {
        return ['student_id' => ['required', 'integer', 'exists:users,id']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $studentId = $this->integer('student_id');

            if ($studentId && ! User::whereKey($studentId)->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', 'student'))->exists()) {
                $validator->errors()->add('student_id', 'فقط دانش‌آموز فعال قابل اتصال به نتیجه آزمون است.');
            }
        }];
    }
}
