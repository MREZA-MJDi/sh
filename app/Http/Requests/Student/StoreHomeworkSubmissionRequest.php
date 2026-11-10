<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

final class StoreHomeworkSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('homework.submit') ?? false;
    }

    public function rules(): array
    {
        return [
            'answer' => ['nullable', 'string', 'max:12000'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,jpg,jpeg,png,zip',
                'max:10240',
            ],
        ];
    }
}
