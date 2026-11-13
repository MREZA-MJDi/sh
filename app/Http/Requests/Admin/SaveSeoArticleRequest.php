<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSeoArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('seo.manage') ?? false;
    }

    public function rules(): array
    {
        $article = $this->route('article');
        $slugRule = Rule::unique('seo_articles', 'slug');
        if ($article) {
            $slugRule->ignore($article->id);
        }

        return [
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['nullable', 'string', 'max:190', $slugRule],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:100000'],
            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_alt' => ['nullable', 'string', 'max:180'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
            'og_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled', 'archived'])],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            if ($this->input('status') === 'scheduled' && ! $this->filled('published_at')) {
                $validator->errors()->add('published_at', 'برای انتشار زمان‌بندی‌شده، تاریخ انتشار الزامی است.');
            }

            $publicationTimestamp = $this->filled('published_at') ? strtotime((string) $this->input('published_at')) : false;
            if ($this->input('status') === 'scheduled' && $publicationTimestamp !== false && $publicationTimestamp <= time()) {
                $validator->errors()->add('published_at', 'زمان انتشار زمان‌بندی‌شده باید در آینده باشد.');
            }

            if ($this->hasFile('featured_image') && ! $this->filled('image_alt')) {
                $validator->errors()->add('image_alt', 'برای تصویر شاخص، متن جایگزین الزامی است.');
            }
        }];
    }
}
