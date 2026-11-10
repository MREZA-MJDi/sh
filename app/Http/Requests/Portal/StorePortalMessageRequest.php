<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

final class StorePortalMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('messages.send.own') ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'recipient_id' => ['required', 'integer', 'exists:users,id', 'different:student_id'],
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
