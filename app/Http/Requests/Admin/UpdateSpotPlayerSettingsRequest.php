<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpotPlayerSettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('integrations.manage') ?? false; }

    public function rules(): array
    {
        return [
            'account_id' => ['nullable', 'string', 'max:190'],
            'api_key' => ['nullable', 'string', 'max:5000'],
            'max_devices' => ['required', 'integer', 'between:1,99'],
            'mode' => ['required', Rule::in(['test', 'production'])],
            'watermark_enabled' => ['sometimes', 'boolean'],
            'auto_license_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
