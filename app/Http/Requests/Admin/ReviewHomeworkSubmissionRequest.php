<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewHomeworkSubmissionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('homework.grade') ?? false; }

    public function rules(): array
    {
        return [
            'score' => ['nullable', 'numeric', 'min:0', 'max:20'],
            'feedback' => ['required', 'string', 'max:30000'],
        ];
    }
}
