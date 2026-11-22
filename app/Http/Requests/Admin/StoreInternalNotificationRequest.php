<?php

namespace App\\Http\\Requests\\Admin;

use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;

final class StoreInternalNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('notifications.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:1000'],
            'audience' => ['required', Rule::in(['all', 'students', 'parents', 'teachers', 'admins'])],
            'url' => ['nullable', 'string', 'max:2048', function (string $attribute, mixed $value, \\Closure $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }

                $url = trim((string) $value);
                $parts = parse_url($url);
                if (! str_starts_with($url, '/') || str_starts_with($url, '//')
                    || ! empty($parts['scheme']) || ! empty($parts['host'])) {
                    $fail('لینک اعلان باید مسیر داخلی پنل یا سایت باشد؛ آدرس خارجی مجاز نیست.');
                }
            }],
            'action_label' => ['nullable', 'string', 'max:80', 'required_with:url'],
        ];
    }
}
