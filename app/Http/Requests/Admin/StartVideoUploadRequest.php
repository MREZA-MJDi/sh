<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class StartVideoUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission([
            'courses.manage', 'integrations.manage', 'videos.manage.assigned',
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'in:teaser,spotplayer_video'],
            'target_id' => ['required', 'integer', 'exists:courses,id'],
            'total_bytes' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'title' => ['required_if:target,spotplayer_video', 'nullable', 'string', 'max:180'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'class_session_id' => ['nullable', 'integer', 'exists:class_sessions,id'],
            'external_id' => ['nullable', 'string', 'max:190'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required_if' => 'عنوان ویدیو را وارد کنید.',
            'target_id.exists' => 'دورهٔ انتخاب‌شده پیدا نشد.',
            'classroom_id.exists' => 'کلاس انتخاب‌شده پیدا نشد.',
            'class_session_id.exists' => 'جلسهٔ انتخاب‌شده پیدا نشد.',
        ];
    }
}
