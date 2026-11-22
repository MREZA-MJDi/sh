<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminSpotPlayerVideoIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission([
            'integrations.manage',
            'videos.manage.assigned',
            'videos.view.assigned',
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'processing', 'failed', 'waiting_contract'])],
            'source' => ['nullable', Rule::in(['local', 'external'])],
            'approval' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }
}
