<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

final class ReplyPortalMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('messages.send.own') ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:30000'],
        ];
    }
}
