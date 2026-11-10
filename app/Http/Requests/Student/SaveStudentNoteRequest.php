<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveStudentNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('notes.manage.own') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('body') && $this->has('text')) {
            $this->merge(['body' => $this->input('text')]);
        }
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
            'tone' => ['nullable', 'string', Rule::in(['', 'b', 'p'])],
        ];
    }
}
