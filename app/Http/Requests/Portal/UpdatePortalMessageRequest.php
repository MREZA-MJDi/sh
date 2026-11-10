<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePortalMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('messages.send.own') ?? false;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
