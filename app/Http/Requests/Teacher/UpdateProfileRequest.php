<?php

namespace App\Http\Requests\Teacher;

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
            'subject' => is_string($this->input('subject')) ? trim($this->input('subject')) : $this->input('subject'),
            'profile_note' => is_string($this->input('profile_note')) ? trim($this->input('profile_note')) : $this->input('profile_note'),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->hasRole('teacher') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:160'],
            'phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
            'subject' => ['nullable', 'string', 'max:100'],
            'profile_note' => ['nullable', 'string', 'max:5000'],
            'avatar' => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp', 'max:8192],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
