<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('announcements.manage') ?? false; }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['draft', 'published', 'archived'])]];
    }
}
