<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        if (is_string($this->input('name'))) {
            $values['name'] = trim($this->input('name'));
        }

        if (is_string($this->input('email'))) {
            $values['email'] = mb_strtolower(trim($this->input('email')));
        }

        if (is_string($this->input('phone'))) {
            $values['phone'] = trim($this->input('phone'));
        }

        $this->merge($values);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ];
    }
}
