<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClassSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['classes.manage', 'classes.manage.assigned']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'session_type' => ['required', Rule::in(['online', 'onsite'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at', 'required_if:session_type,online'],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2000'],
            'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
            'teacher_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان جلسه را وارد کنید.',
            'starts_at.required' => 'زمان شروع جلسه را مشخص کنید.',
            'ends_at.after' => 'زمان پایان باید بعد از زمان شروع باشد.',
            'meeting_url.url' => 'لینک جلسه باید با http یا https شروع شود.',
        ];
    }
}