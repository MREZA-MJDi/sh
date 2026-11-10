<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class SubmitInternalExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('student')
            && $this->user()?->hasPermissionTo('exams.attempt');
    }

    public function rules(): array
    {
        return [
            'answers' => ['nullable', 'array', 'max:100'],
            'answers.*' => ['nullable'],
        ];
    }
}
