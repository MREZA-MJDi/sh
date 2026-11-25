<?php

namespace App\Http\Requests\Admin;

use App\Models\SeoBanner;
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
            'subtitle' => ['nullable', 'string', 'max:1000'],
            'badge' => ['nullable', 'string', 'max:120'],
            'image' => [$this->isMethod('post') ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
            'image_position' => ['nullable', 'regex:/^(100|[1-9]?\d)%\s+(100|[1-9]?\d)%$/'],
            'bg_color' => ['nullable', 'string', 'max:120'],
            'text_color' => ['nullable', 'string', 'max:50'],
            'layout_type' => ['nullable', Rule::in(['side_image', 'full_background'])],
            'placement' => ['required', Rule::in(['home_hero', 'articles_top', 'articles_sidebar', 'sitewide'])],
            'target_url' => ['nullable', 'string', 'max:2048'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'secondary_button_text' => ['nullable', 'string', 'max:100'],
            'secondary_button_url' => ['nullable', 'string', 'max:2048'],
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
            $validateUrl = function (string $field, string $label) use ($validator) {
                $url = trim((string) $this->input($field));
                if ($url === '') {
                    return;
                }
                $isInternalPath = str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, chr(92));
                $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
                $isSafeExternalUrl = filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true);

                if (! $isInternalPath && ! $isSafeExternalUrl) {
                    $validator->errors()->add($field, "{$label} باید یک مسیر داخلی (مانند /courses) یا نشانی کامل اینترنتی معتبر باشد.");
                }
                if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
                    $validator->errors()->add($field, "{$label} مجاز نیست.");
                }
            };

            if ($this->input('placement') === 'home_hero') {
                $bannerId = $this->route('banner')?->id;
                $heroCount = SeoBanner::query()
                    ->where('placement', 'home_hero')
                    ->when($bannerId, fn ($query) => $query->where('id', '!=', $bannerId))
                    ->count();

                if ($heroCount >= 3) {
                    $validator->errors()->add('placement', 'اسلایدر صفحه اصلی حداکثر ۳ تصویر دارد؛ یکی از اسلایدهای فعلی را ویرایش یا حذف کنید.');
                }
            }

            $validateUrl('target_url', 'نشانی مقصد');
            $validateUrl('secondary_button_url', 'نشانی دکمه دوم');
        }];
    }
}
