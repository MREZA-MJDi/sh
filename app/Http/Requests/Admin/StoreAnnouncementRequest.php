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
        ];
    }
}
