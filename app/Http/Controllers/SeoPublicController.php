<?php

namespace App\Http\Controllers;

use App\Models\SeoArticle;
use App\Models\SeoBanner;
use App\Models\SeoSetting;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SeoPublicController extends Controller
{
    public function index(): View
    {
        $settings = SeoSetting::current();
        $articles = SeoArticle::published()->with('author')->latest('published_at')->paginate(12);
        $banners = SeoBanner::active()
            ->whereIn('placement', ['articles_top', 'sitewide'])
            ->orderBy('sort_order')
            ->get();

        return view('seo.public.index', compact('settings', 'articles', 'banners'));
    }

    public function show(string $slug): View
    {
        $settings = SeoSetting::current();
        $article = SeoArticle::published()->with('author')->where('slug', $slug)->firstOrFail();
        $banners = SeoBanner::active()
            ->whereIn('placement', ['articles_sidebar', 'sitewide'])
            ->orderBy('sort_order')
            ->get();

        return view('seo.public.show', compact('settings', 'article', 'banners'));
    }

    public function sitemap(): Response
    {
        $settings = SeoSetting::current();
        abort_unless($settings->sitemap_enabled, 404);

        $baseUrl = rtrim($settings->canonical_base_url ?: url('/'), '/');
        $articles = $settings->robots_index
            ? SeoArticle::published()->orderBy('published_at')->get(['slug', 'updated_at'])
            : collect();

        $urls = collect();
        if ($settings->robots_index) {
            $urls->push([
                'loc' => $baseUrl.'/articles',
                'lastmod' => now()->toAtomString(),
            ]);
            $urls = $urls->merge($articles->map(fn (SeoArticle $article): array => [
                'loc' => $baseUrl.'/articles/'.rawurlencode($article->slug),
                'lastmod' => $article->updated_at?->toAtomString(),
            ]));
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url><loc>'.e($url['loc']).'</loc>';
            if ($url['lastmod']) {
                $xml .= '<lastmod>'.e($url['lastmod']).'</lastmod>';
            }
            $xml .= '</url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $settings = SeoSetting::current();
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /student',
            'Disallow: /teacher',
            'Disallow: /parent',
            'Disallow: /login',
            'Disallow: /register',
        ];

        if ($settings->sitemap_enabled) {
            $lines[] = 'Sitemap: '.rtrim($settings->canonical_base_url ?: url('/'), '/').'/sitemap.xml';
        }

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
