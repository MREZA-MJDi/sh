<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProfileRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $digits = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];

        $phone = $this->input('phone');
        if (is_string($phone)) {
            $phone = strtr(trim($phone), $digits);
            $phone = preg_replace('/[\s-]+/u', '', $phone) ?? $phone;
        }

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'phone' => $phone,
            'remove_avatar' => $this->boolean('remove_avatar'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('profile.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
            'avatar' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:4096'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
