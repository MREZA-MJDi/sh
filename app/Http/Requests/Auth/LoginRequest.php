<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $identifier = mb_strtolower(trim((string) $this->input('email', '')));
        $digits = strtr($identifier, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge([
            'email' => filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? $identifier
                : (preg_replace('/[\\s-]+/u', '', $digits) ?? $digits),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
