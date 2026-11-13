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
        ];
    }
}
