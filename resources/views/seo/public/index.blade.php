@extends('public.layouts.academy')

@section('title', ($settings->home_sections['blog_page_title'] ?? 'مجله شیخان') . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'شیخان'))
@section('description', filled($settings->home_sections['blog_page_description'] ?? null) ? $settings->home_sections['blog_page_description'] : $settings->default_description)
@section('canonical', rtrim($settings->canonical_base_url ?: url('/'), '/').'/articles')

@php($featuredSeoArticle = $articles->first())
@php($articlesToLoop = ($featuredSeoArticle && empty($search) && $articles->currentPage() === 1) ? $articles->slice(1) : $articles)
@section('seo-image', $featuredSeoArticle?->featured_image_path ? Storage::disk('public')->url($featuredSeoArticle->og_image_path ?: $featuredSeoArticle->featured_image_path) : ($settings->default_og_image_path ? Storage::disk('public')->url($settings->default_og_image_path) : ''))
@push('head')
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $settings->home_sections['blog_page_title'] ?? 'مجله شیخان' }}">
    <meta property="og:description" content="{{ filled($settings->home_sections['blog_page_description'] ?? null) ? $settings->home_sections['blog_page_description'] : $settings->default_description }}">
    <meta property="og:url" content="{{ rtrim($settings->canonical_base_url ?: url('/'), '/') }}/articles">
    @if($featuredSeoArticle?->featured_image_path)
        <meta property="og:image:alt" content="{{ $featuredSeoArticle->image_alt ?: $featuredSeoArticle->title }}">
        <meta name="twitter:image:alt" content="{{ $featuredSeoArticle->image_alt ?: $featuredSeoArticle->title }}">
    @endif
@endpush

@section('content')
    {{-- Header Banner --}}
    <section class="contact-hero-wrap">
        <div class="c">
            <header class="contact-hero">
                <div class="contact-hero-copy">
                    <div class="contact-breadcrumb">
                        <a href="{{ route('home') }}">خانه</a>
                        <span>‹</span>
                        <span>مقالات</span>
                    </div>
                    <h1>{{ $settings->home_sections['blog_page_title'] ?? 'مجله شیخان' }}</h1>
                    <p>{{ filled($settings->home_sections['blog_page_description'] ?? null) ? $settings->home_sections['blog_page_description'] : $settings->default_description }}</p>
                </div>

                <div class="contact-hero-highlights">
                    <div class="contact-highlight-pill">
                        <svg class="i"><use href="#pen"/></svg>
                        <span>مقالات تخصصی آموزشی و مشاوره‌ای</span>
                    </div>
                    <div class="contact-highlight-pill">
                        <svg class="i"><use href="#award"/></svg>
                        <span>نکات کاربردی مطالعه و آمادگی آزمون</span>
                    </div>
                    <div class="contact-highlight-pill">
                        <svg class="i"><use href="#search"/></svg>
                        <span>جستجو و فیلتر بر اساس کلیدواژه</span>
                    </div>
                </div>
            </header>
        </div>
    </section>

    <div class="c" style="padding-top:32px;padding-bottom:48px">
        {{-- Search & Topic Chips --}}
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px">
            <div class="chips" style="margin:0">
                <a class="chip {{ empty($keyword) ? 'on' : '' }}" href="{{ route('seo.articles.index') }}">همه</a>
                @foreach($keywords as $item)
                    <a class="chip {{ $keyword === $item ? 'on' : '' }}" href="{{ route('seo.articles.index', ['keyword' => $item]) }}">{{ $item }}</a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('seo.articles.index') }}" style="display:flex;gap:8px;max-width:320px;width:100%">
                @if($keyword)<input type="hidden" name="keyword" value="{{ $keyword }}">@endif
                <input class="inp" style="margin:0;padding:8px 14px;font-size:13px" type="search" name="q" value="{{ $search }}" placeholder="جستجو در مقالات...">
                <button class="btn s" type="submit" style="padding:8px 14px">جستجو</button>
            </form>
        </div>

        {{-- Banners if any --}}
        @if(isset($banners) && $banners->isNotEmpty())
            <div style="margin-bottom:24px">
                @foreach($banners as $banner)
                    <div style="border-radius:14px;overflow:hidden;margin-bottom:12px">
                        @if($banner->target_url)<a href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>@endif
                            <img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" style="width:100%;height:auto;display:block" loading="lazy">
                        @if($banner->target_url)</a>@endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($featuredSeoArticle && empty($search) && $articles->currentPage() === 1)
            {{-- Featured Article --}}
            <a class="card feat" href="{{ route('seo.articles.show', $featuredSeoArticle->slug) }}">
                <div class="th" style="background:linear-gradient(135deg,#0d9488,#2563eb)">
                    @if($featuredSeoArticle->featured_image_path)
                        <img src="{{ Storage::disk('public')->url($featuredSeoArticle->featured_image_path) }}" alt="{{ $featuredSeoArticle->image_alt ?: $featuredSeoArticle->title }}" style="object-position: {{ $featuredSeoArticle->image_position ?? '50% 50%' }}" loading="lazy">
                    @else
                        <svg class="i"><use href="#pen"/></svg>
                    @endif
                </div>
                <div class="cb">
                    <span class="bd">{{ !empty($featuredSeoArticle->keywords) ? $featuredSeoArticle->keywords[0] : 'مقاله برگزیده' }}</span>
                    <h2>{{ $featuredSeoArticle->title }}</h2>
                    <p>{{ $featuredSeoArticle->excerpt }}</p>
                    <div class="mt" style="margin-bottom:16px">
                        <span><svg class="i" style="width:14px;height:14px;vertical-align:middle;margin-left:4px"><use href="#clock"/></svg>{{ $featuredSeoArticle->published_at ? $featuredSeoArticle->published_at->format('Y/m/d') : '' }}</span>
                        <span>نویسنده: {{ $featuredSeoArticle->author->name ?? 'شیخان' }}</span>
                    </div>
                    <div><span class="btn s">ادامه مطلب</span></div>
                </div>
            </a>
        @endif

        {{-- Article Cards Grid --}}
        <div class="g g3">
            @if($articlesToLoop->isNotEmpty())
                @each('seo.public.partials.article-card', $articlesToLoop, 'article')
            @else
                <div class="card" style="grid-column: 1 / -1; padding: 48px; text-align: center; color: var(--mute)">
                    <p style="font-size: 16px; margin-bottom: 12px">مقاله‌ای با این مشخصات یافت نشد.</p>
                    <a class="btn s" href="{{ route('seo.articles.index') }}">مشاهده همه مقالات</a>
                </div>
            @endif
        </div>

        {{-- Pagination --}}
        <div style="margin-top:36px">
            {{ $articles->links() }}
        </div>


    </div>
@endsection
