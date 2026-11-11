<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSeoArticleRequest;
use App\Http\Requests\Admin\SaveSeoBannerRequest;
use App\Http\Requests\Admin\UpdateSeoSettingsRequest;
use App\Models\SeoArticle;
use App\Models\SeoBanner;
use App\Models\SeoSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoDashboardController extends Controller
{
    public function index(): View
    {
        $settings = SeoSetting::current();

        return view('admin.seo.index', [
            'settings' => $settings,
            'articleCount' => SeoArticle::query()->count(),
            'publishedArticleCount' => SeoArticle::published()->count(),
            'draftArticleCount' => SeoArticle::query()->where('status', 'draft')->orWhere(fn ($query) => $query->where('status', 'scheduled')->where('published_at', '>', now()))->count(),
            'bannerCount' => SeoBanner::query()->count(),
            'activeBannerCount' => SeoBanner::active()->count(),
            'recentArticles' => SeoArticle::query()->latest()->limit(5)->get(),
            'recentBanners' => SeoBanner::query()->orderBy('sort_order')->latest()->limit(5)->get(),
        ]);
    }

    public function updateSettings(UpdateSeoSettingsRequest $request): RedirectResponse
    {
        $settings = SeoSetting::current();
        $data = $request->validated();
        $oldPaths = [];

        foreach (['default_og_image' => 'default_og_image_path', 'organization_logo' => 'organization_logo_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $oldPaths[] = $settings->{$column};
                $data[$column] = $request->file($input)->store('seo/settings', 'public');
            }
            unset($data[$input]);
        }

        $data['default_keywords'] = collect(preg_split('/[,،]+/u', (string) ($data['default_keywords'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $keyword) => trim($keyword))
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();

        if (array_key_exists('social_links', $data)) {
            $data['social_links'] = array_filter($data['social_links'] ?? [], fn ($url) => filled($url));
        }

        $data['robots_index'] = $request->boolean('robots_index');
        $data['robots_follow'] = $request->boolean('robots_follow');
        $data['sitemap_enabled'] = $request->boolean('sitemap_enabled');
        $data['updated_by'] = $request->user()->id;

        $settings->update($data);
        $this->deleteStoredFiles($oldPaths);

        return back()->with('success', 'تنظیمات سئو ذخیره شد.');
    }

    public function banners(): View
    {
        $banners = SeoBanner::query()->with('creator')
            ->orderBy('sort_order')->latest('id')->paginate(15)->withQueryString();

        return view('admin.seo.banners', compact('banners'));
    }

    public function storeBanner(SaveSeoBannerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['image_path'] = $request->file('image')->store('seo/banners', 'public');
        unset($data['image']);
        $data['created_by'] = $request->user()->id;
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');

        SeoBanner::query()->create($data);

        return back()->with('success', 'بنر با موفقیت ثبت شد.');
    }

    public function updateBanner(SaveSeoBannerRequest $request, SeoBanner $banner): RedirectResponse
    {
        $data = $request->validated();
        $oldPath = null;

        if ($request->hasFile('image')) {
            $oldPath = $banner->image_path;
            $data['image_path'] = $request->file('image')->store('seo/banners', 'public');
        }
        unset($data['image']);
        $data['open_in_new_tab'] = $request->boolean('open_in_new_tab');

        $banner->update($data);
        $this->deleteStoredFiles([$oldPath]);

        return back()->with('success', 'بنر به‌روزرسانی شد.');
    }

    public function destroyBanner(SeoBanner $banner): RedirectResponse
    {
        $path = $banner->image_path;
        $banner->delete();
        $this->deleteStoredFiles([$path]);

        return back()->with('success', 'بنر حذف شد.');
    }

    public function articles(): View
    {
        $articles = SeoArticle::query()->with('author')->latest()->paginate(15)->withQueryString();

        return view('admin.seo.articles', compact('articles'));
    }

    public function storeArticle(SaveSeoArticleRequest $request): RedirectResponse
    {
        $data = $this->articleData($request);
        $data['author_id'] = $request->user()->id;
        $data['featured_image_path'] = $request->file('featured_image')?->store('seo/articles', 'public');
        $data['og_image_path'] = $request->file('og_image')?->store('seo/articles/og', 'public');
        unset($data['featured_image'], $data['og_image']);

        SeoArticle::query()->create($data);

        return back()->with('success', 'مقاله ذخیره شد.');
    }

    public function updateArticle(SaveSeoArticleRequest $request, SeoArticle $article): RedirectResponse
    {
        $data = $this->articleData($request, $article);
        $oldPaths = [];

        if ($request->hasFile('featured_image')) {
            $oldPaths[] = $article->featured_image_path;
            $data['featured_image_path'] = $request->file('featured_image')->store('seo/articles', 'public');
        }
        if ($request->hasFile('og_image')) {
            $oldPaths[] = $article->og_image_path;
            $data['og_image_path'] = $request->file('og_image')->store('seo/articles/og', 'public');
        }
        unset($data['featured_image'], $data['og_image']);

        $article->update($data);
        $this->deleteStoredFiles($oldPaths);

        return back()->with('success', 'مقاله به‌روزرسانی شد.');
    }

    public function destroyArticle(SeoArticle $article): RedirectResponse
    {
        $paths = [$article->featured_image_path, $article->og_image_path];
        $article->delete();
        $this->deleteStoredFiles($paths);

        return back()->with('success', 'مقاله حذف شد.');
    }

    private function articleData(SaveSeoArticleRequest $request, ?SeoArticle $article = null): array
    {
        $data = $request->validated();
        $slug = trim((string) ($data['slug'] ?? ''));

        if ($slug === '') {
            $slug = $article?->slug ?: Str::slug($data['title']);
            if ($slug === '') {
                $slug = 'article-'.Str::lower(Str::random(10));
            }
        } else {
            $slug = Str::slug($slug);
        }
        if ($slug === '') {
            $slug = 'article-'.Str::lower(Str::random(10));
        }

        $baseSlug = $slug;
        $suffix = 2;
        while (SeoArticle::query()->where('slug', $slug)->when($article, fn ($query) => $query->where('id', '!=', $article->id))->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        $data['slug'] = $slug;
        $data['keywords'] = collect(preg_split('/[,،]+/u', (string) ($data['keywords'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn (string $keyword) => trim($keyword))
            ->filter()
            ->unique()
            ->take(20)
            ->values()
            ->all();

        if ($data['status'] === 'published') {
            $data['published_at'] = $data['published_at'] ?? $article?->published_at ?? now();
        } elseif ($data['status'] === 'draft') {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function deleteStoredFiles(array $paths): void
    {
        $paths = array_values(array_unique(array_filter($paths)));
        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
    }
}
