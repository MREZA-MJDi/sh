<?php

namespace App\\Http\\Controllers;

use App\\Models\\Course;
use App\\Models\\SeoArticle;
use App\\Models\\SeoBanner;
use App\\Models\\SeoSetting;
use App\\Models\\User;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Http\\Request;
use Illuminate\\Http\\Response;
use Illuminate\\View\\View;

class SeoPublicController extends Controller
{
    private const SITEMAP_URL_LIMIT = 50000;

    public function index(): View
    {
        $settings = SeoSetting::current();
        $keyword = trim((string) request()->query('keyword'));
        $search = trim((string) request()->query('q'));
        $articleQuery = SeoArticle::published()->with('author');
        if ($keyword !== '') {
            $articleQuery->whereJsonContains('keywords', $keyword);
        }
        if ($search !== '') {
            $articleQuery->where(fn ($query) => $query
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('excerpt', 'like', '%'.$search.'%'));
        }
        $articles = $articleQuery->latest('published_at')->paginate(12)->withQueryString();
        $keywords = SeoArticle::published()->latest('published_at')->limit(250)->pluck('keywords')->flatten()
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->unique()->sort()->values();
        $banners = SeoBanner::active()
            ->whereIn('placement', ['articles_top', 'sitewide'])
            ->orderBy('sort_order')->orderBy('id')->limit(6)
            ->get();

        return view('seo.public.index', compact('settings', 'articles', 'banners', 'keywords', 'keyword', 'search'));
    }

    public function show(string $slug): View
    {
        $settings = SeoSetting::current();
        $article = SeoArticle::published()->with('author')->where('slug', $slug)->firstOrFail();
        $banners = SeoBanner::active()
            ->whereIn('placement', ['articles_sidebar', 'sitewide'])
            ->orderBy('sort_order')->orderBy('id')->limit(6)
            ->get();

        $relatedArticles = SeoArticle::published()
            ->where('id', '!=', $article->id)
            ->latest('published_at')->orderByDesc('id')
            ->limit(4)
            ->get();

        $relatedCourses = Course::query()
            ->where('status', 'published')
            ->select(['id', 'title', 'slug', 'teacher_id', 'type', 'price', 'subject', 'grade', 'description'])
            ->with([
                'teacher:id,name',
                'sections' => fn ($sections) => $sections->where('status', 'published')
                    ->withCount(['lessons' => fn ($lessons) => $lessons->where('status', 'published')]),
            ])
            ->latest('updated_at')->limit(2)
            ->get();

        return view('seo.public.show', compact('settings', 'article', 'banners', 'relatedArticles', 'relatedCourses'));
    }

    /**
     * The public sitemap endpoint is an index. Each child sitemap contains at
     * most 50,000 URLs, regardless of how much content the site accumulates.
     */
    public function sitemap(): Response
    {
        $settings = SeoSetting::current();
        abort_unless($settings->sitemap_enabled, 404);

        if (! $settings->robots_index) {
            return $this->urlsetResponse([]);
        }

        $baseUrl = rtrim($settings->canonical_base_url ?: url('/'), '/');
        $sitemaps = [
            $baseUrl.'/sitemap/static.xml',
        ];

        $sources = [
            'courses' => Course::query()->where('status', 'published'),
            'teachers' => User::query()->where('is_active', true)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher')),
            'articles' => SeoArticle::published(),
        ];

        foreach ($sources as $type => $query) {
            $pages = max(1, (int) ceil((clone $query)->count() / self::SITEMAP_URL_LIMIT));

            for ($page = 1; $page <= $pages; $page++) {
                $sitemaps[] = $baseUrl.'/sitemap/'.$type.'.xml?page='.$page;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($sitemaps as $loc) {
            $xml .= '<sitemap><loc>'.$this->xmlEscape($loc).'</loc><lastmod>'.$this->xmlEscape(now()->toAtomString()).'</lastmod></sitemap>';
        }
        $xml .= '</sitemapindex>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function sitemapSection(Request $request, string $type): Response
    {
        $settings = SeoSetting::current();
        abort_unless($settings->sitemap_enabled, 404);
        abort_unless(in_array($type, ['static', 'courses', 'teachers', 'articles'], true), 404);

        if (! $settings->robots_index) {
            return $this->urlsetResponse([]);
        }

        $baseUrl = rtrim($settings->canonical_base_url ?: url('/'), '/');

        if ($type === 'static') {
            return $this->urlsetResponse([
                ['loc' => $baseUrl.'/', 'lastmod' => now()->toAtomString()],
                ['loc' => $baseUrl.'/courses', 'lastmod' => now()->toAtomString()],
                ['loc' => $baseUrl.'/teachers', 'lastmod' => now()->toAtomString()],
                ['loc' => $baseUrl.'/contact', 'lastmod' => now()->toAtomString()],
                ['loc' => $baseUrl.'/articles', 'lastmod' => now()->toAtomString()],
            ]);
        }

        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * self::SITEMAP_URL_LIMIT;

        $urls = match ($type) {
            'courses' => Course::query()
                ->where('status', 'published')
                ->orderBy('id')
                ->offset($offset)->limit(self::SITEMAP_URL_LIMIT)
                ->get(['slug', 'updated_at'])
                ->map(fn (Course $course): array => [
                    'loc' => $baseUrl.'/courses/'.rawurlencode($course->slug),
                    'lastmod' => $course->updated_at?->toAtomString(),
                ])->all(),

            'teachers' => User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'teacher'))
                ->orderBy('id')
                ->offset($offset)->limit(self::SITEMAP_URL_LIMIT)
                ->get(['id', 'updated_at'])
                ->map(fn (User $teacher): array => [
                    'loc' => $baseUrl.'/teachers/'.$teacher->id,
                    'lastmod' => $teacher->updated_at?->toAtomString(),
                ])->all(),

            'articles' => SeoArticle::published()
                ->orderBy('id')
                ->offset($offset)->limit(self::SITEMAP_URL_LIMIT)
                ->get(['slug', 'canonical_url', 'updated_at'])
                ->map(fn (SeoArticle $article): array => [
                    'loc' => $this->safeCanonicalUrl($article->canonical_url, $baseUrl.'/articles/'.rawurlencode($article->slug)),
                    'lastmod' => $article->updated_at?->toAtomString(),
                ])->all(),
        };

        return $this->urlsetResponse($urls);
    }

    /**
     * @param  array<int, array{loc:string,lastmod:?string}>  $urls
     */
    private function urlsetResponse(array $urls): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url><loc>'.$this->xmlEscape($url['loc']).'</loc>';
            if (! empty($url['lastmod'])) {
                $xml .= '<lastmod>'.$this->xmlEscape($url['lastmod']).'</lastmod>';
            }
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function safeCanonicalUrl(?string $candidate, string $fallback): string
    {
        if (! is_string($candidate) || trim($candidate) === '') {
            return $fallback;
        }

        $scheme = parse_url($candidate, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['http', 'https'], true)
            ? $candidate
            : $fallback;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
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
            'Disallow: /cart',
        ];

        if ($settings->sitemap_enabled) {
            $lines[] = 'Sitemap: '.rtrim($settings->canonical_base_url ?: url('/'), '/').'/sitemap.xml';
        }

        return response(implode("\\n", $lines)."\\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
