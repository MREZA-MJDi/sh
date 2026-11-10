<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuiz24ExamRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('exams.manage') ?? false; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'provider' => ['required', Rule::in(['internal', 'quiz24'])],
            'external_id' => [
                'nullable',
                'string',
                'max:190',
                'prohibited_unless:provider,quiz24',
                Rule::unique('exams', 'external_id')->where('provider', 'quiz24'),
            ],
            'starts_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'closed'])],
        ];
    }
}
