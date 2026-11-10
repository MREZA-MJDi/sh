<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpotPlayerVideoRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('integrations.manage') ?? false; }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'external_id' => ['nullable', 'string', 'max:190'],
            'embed_url' => ['nullable', 'url:https', 'max:2000'],
            'status' => ['required', Rule::in(['draft', 'waiting_contract'])],
        ];
    }
}
