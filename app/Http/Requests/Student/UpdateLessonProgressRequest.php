<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLessonProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('student')
            && $this->user()?->hasPermissionTo('lessons.progress');
    }

    public function rules(): array
    {
        return [
            'progress_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
