<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class SubmitHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('homework.submit') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('answer') && $this->has('text')) {
            $this->merge(['answer' => $this->input('text')]);
        }
    }

    public function rules(): array
    {
        return [
            'answer' => ['nullable', 'string', 'max:12000'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png',
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasAnswer = filled($this->input('answer'));
            $hasFiles = $this->hasFile('files') || $this->hasFile('attachment');

            if (! $hasAnswer && ! $hasFiles) {
                $validator->errors()->add('answer', 'متن پاسخ یا دست‌کم یک فایل را ارسال کنید.');
            }
        });
    }
}
