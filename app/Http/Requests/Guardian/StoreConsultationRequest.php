<?php

namespace App\Http\Requests\Guardian;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('consultations.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(['ticket', 'phone', 'in_person'])],
            'topic' => ['required', 'string', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'desired_at' => ['nullable', 'date', 'after:now'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $studentId = $this->integer('student_id');
            $isLinkedStudent = $this->user()?->children()
                ->whereKey($studentId)
                ->where('users.is_active', true)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'student'))
                ->exists();

            if (! $isLinkedStudent) {
                $validator->errors()->add('student_id', 'دانش‌آموز انتخاب‌شده به حساب شما متصل نیست.');
            }
        }];
    }
}
