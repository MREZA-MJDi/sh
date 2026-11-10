<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RequestSpotPlayerRevocationRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('integrations.manage') ?? false; }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:5', 'max:300']];
    }
}
