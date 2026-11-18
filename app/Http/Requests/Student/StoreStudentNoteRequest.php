<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStudentNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('notes.manage.own') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $tone = $this->input('tone');
        if ($tone === 'default' || $tone === null) {
            $tone = '';
        }

        $this->merge([
            'body' => is_string($this->input('body')) ? trim($this->input('body')) : $this->input('body'),
            'tone' => is_string($tone) ? trim($tone) : $tone,
        ]);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:12000'],
            'tone' => ['nullable', 'string', 'max:12', Rule::in(['', 'default', 'b', 'p', 'blue', 'green', 'amber', 'red', 'purple', 'yellow'])],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'متن یادداشت الزامی است.',
            'body.max' => 'متن یادداشت نمی‌تواند بیشتر از ۱۲۰۰۰ کاراکتر باشد.',
            'tone.in' => 'نوع یا برچسب یادداشت انتخاب‌شده معتبر نیست.',
        ];
    }
}
