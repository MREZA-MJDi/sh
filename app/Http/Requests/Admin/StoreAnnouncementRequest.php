<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('announcements.manage') ?? false; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:40000'],
            'audience' => ['required', Rule::in(['all', 'students', 'parents', 'teachers'])],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'priority' => ['required', Rule::in(['normal', 'important', 'urgent'])],
            'action_url' => ['nullable', 'url', 'max:2048', 'regex:/^https:\\/\\//i'],
            'action_label' => ['nullable', 'string', 'max:80', 'required_with:action_url'],
        ];
    }
}
