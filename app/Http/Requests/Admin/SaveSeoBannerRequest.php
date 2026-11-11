<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveSeoBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('seo.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'alt_text' => ['required', 'string', 'max:180'],
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'placement' => ['required', Rule::in(['articles_top', 'articles_sidebar', 'sitewide'])],
            'target_url' => ['nullable', 'string', 'max:2048'],
            'open_in_new_tab' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $url = trim((string) $this->input('target_url'));
            $isInternalPath = str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, '\\\\');
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            $isSafeExternalUrl = filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true);

            if ($url !== '' && ! $isInternalPath && ! $isSafeExternalUrl) {
                $validator->errors()->add('target_url', 'نشانی مقصد باید یک مسیر داخلی یا نشانی کامل HTTP/HTTPS معتبر باشد.');
            }
            if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
                $validator->errors()->add('target_url', 'نشانی مقصد مجاز نیست.');
            }
        }];
    }
}
