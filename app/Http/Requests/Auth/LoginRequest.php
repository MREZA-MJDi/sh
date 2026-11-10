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
        $identifier = trim((string) $this->input('email', ''));
        $normalizedEmail = mb_strtolower($identifier);
        $digits = strtr($identifier, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $isPhone = preg_match('/^[0-9۰-۹٠-٩\s-]+$/u', $identifier) === 1;
        $normalizedIdentifier = filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)
            ? $normalizedEmail
            : ($isPhone ? (preg_replace('/[\s-]+/u', '', $digits) ?? $digits) : $identifier);

        $this->merge(['email' => $normalizedIdentifier]);
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
