<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('seo.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:160'],
            'default_title' => ['required', 'string', 'max:180'],
            'title_separator' => ['required', 'string', 'max:10'],
            'default_description' => ['nullable', 'string', 'max:500'],
            'default_keywords' => ['nullable', 'string', 'max:1000'],
            'canonical_base_url' => ['nullable', 'url:http,https', 'max:2048'],
            'default_og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'organization_name' => ['nullable', 'string', 'max:180'],
            'organization_url' => ['nullable', 'url:http,https', 'max:2048'],
            'organization_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'twitter_handle' => ['nullable', 'string', 'max:80', 'regex:/^@?[A-Za-z0-9_]+$/'],
            'google_site_verification' => ['nullable', 'string', 'max:255'],
            'bing_site_verification' => ['nullable', 'string', 'max:255'],
            'robots_index' => ['sometimes', 'boolean'],
            'robots_follow' => ['sometimes', 'boolean'],
            'sitemap_enabled' => ['sometimes', 'boolean'],
            'social_links' => ['nullable', 'array', 'max:8'],
            'social_links.*' => ['nullable', 'url:http,https', 'max:2048'],
            'trust_badge' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'footer_description' => ['nullable', 'string', 'max:500'],
            'footer_links' => ['nullable', 'array', 'max:8'],
            'footer_links.*' => ['array'],
            'footer_links.*.label' => ['nullable', 'string', 'max:80'],
            'footer_links.*.url' => ['nullable', 'string', 'max:2048'],
            'home_sections' => ['nullable', 'array'],
            'home_sections.*' => ['nullable'],
            'home_sections.steps' => ['nullable', 'array', 'max:4'],
            'home_sections.steps.*' => ['array'],
            'home_sections.steps.*.title' => ['nullable', 'string', 'max:180'],
            'home_sections.steps.*.body' => ['nullable', 'string', 'max:500'],
            'home_sections.panel_cards' => ['nullable', 'array', 'max:4'],
            'home_sections.panel_cards.*' => ['array'],
            'home_sections.panel_cards.*.title' => ['nullable', 'string', 'max:180'],
            'home_sections.panel_cards.*.body' => ['nullable', 'string', 'max:500'],
            'home_sections.benefits' => ['nullable', 'array', 'max:6'],
            'home_sections.benefits.*' => ['array'],
            'home_sections.benefits.*.title' => ['nullable', 'string', 'max:180'],
            'home_sections.benefits.*.body' => ['nullable', 'string', 'max:500'],
            'home_sections.faq_items' => ['nullable', 'array', 'max:8'],
            'home_sections.faq_items.*' => ['array'],
            'home_sections.faq_items.*.question' => ['nullable', 'string', 'max:240'],
            'home_sections.faq_items.*.answer' => ['nullable', 'string', 'max:1200'],
            'home_sections.contact_topics' => ['nullable', 'array', 'max:8'],
            'home_sections.contact_topics.*' => ['nullable', 'string', 'max:80'],
            'home_sections.contact_faq_items' => ['nullable', 'array', 'max:8'],
            'home_sections.contact_faq_items.*' => ['array'],
            'home_sections.contact_faq_items.*.question' => ['nullable', 'string', 'max:240'],
            'home_sections.contact_faq_items.*.answer' => ['nullable', 'string', 'max:1200'],
            'home_sections.stats_labels' => ['nullable', 'array', 'size:4'],
            'home_sections.stats_labels.*' => ['required', 'string', 'max:80'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            foreach ((array) $this->input('footer_links', []) as $index => $link) {
                if (! is_array($link)) continue;
                $label = trim((string) ($link['label'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));
                if ($label === '' && $url === '') continue;
                if ($label === '' || $url === '') {
                    $validator->errors()->add("footer_links.$index.label", 'برای هر لینک فوتر، عنوان و نشانی را با هم وارد کن.');
                    continue;
                }
                $internal = str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_contains($url, chr(92));
                $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
                $external = filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true);
                if (! $internal && ! $external) {
                    $validator->errors()->add("footer_links.$index.url", 'نشانی لینک باید مسیر داخلی یا URL معتبر HTTP/HTTPS باشد.');
                }
            }
        }];
    }
}
