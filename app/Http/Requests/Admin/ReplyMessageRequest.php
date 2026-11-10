<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReplyMessageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('messages.reply') ?? false; }

    public function rules(): array { return ['body' => ['required', 'string', 'max:10000']]; }
}
