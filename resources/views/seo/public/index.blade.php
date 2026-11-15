@extends('public.layouts.academy')

@section('title', ($settings->home_sections['blog_page_title'] ?? 'مجله شیخان') . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'شیخان'))
@section('description', $settings->home_sections['blog_page_description'] ?? $settings->default_description ?? '')
@section('canonical', rtrim($settings->canonical_base_url ?: url('/'), '/').'/articles')

@section('content')
    {{-- Header Banner --}}
    <div class="pgh">
        <div class="c">
            <div class="bc"><a href="{{ route('home') }}">خانه</a> ‹ مقالات</div>
            <h1>{{ $settings->home_sections['blog_page_title'] ?? 'مجله شیخان' }}</h1>
            <p>{{ $settings->home_sections['blog_page_description'] ?? $settings->default_description }}</p>
        </div>
    </div>

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

        @php
            $featured = $articles->first();
            $rest = $articles->slice(1);
        @endphp

        @if($featured && empty($search) && $articles->currentPage() === 1)
            {{-- Featured Article --}}
            <a class="card feat" href="{{ route('seo.articles.show', $featured->slug) }}">
                <div class="th" style="background:linear-gradient(135deg,#0d9488,#2563eb)">
                    @if($featured->featured_image_path)
                        <img src="{{ Storage::disk('public')->url($featured->featured_image_path) }}" alt="{{ $featured->image_alt ?: $featured->title }}" style="object-position: {{ $featured->image_position ?? '50% 50%' }}" loading="lazy">
                    @else
                        <svg class="i"><use href="#pen"/></svg>
                    @endif
                </div>
                <div class="cb">
                    <span class="bd">{{ !empty($featured->keywords) ? $featured->keywords[0] : 'مقاله برگزیده' }}</span>
                    <h2>{{ $featured->title }}</h2>
                    <p>{{ $featured->excerpt }}</p>
                    <div class="mt" style="margin-bottom:16px">
                        <span><svg class="i" style="width:14px;height:14px;vertical-align:middle;margin-left:4px"><use href="#clock"/></svg>{{ $featured->published_at ? $featured->published_at->format('Y/m/d') : '' }}</span>
                        <span>نویسنده: {{ $featured->author->name ?? 'شیخان' }}</span>
                    </div>
                    <div><span class="btn s">ادامه مطلب</span></div>
                </div>
            </a>
        @endif

        {{-- Article Cards Grid --}}
        <div class="g g3">
            @php $articlesToLoop = ($featured && empty($search) && $articles->currentPage() === 1) ? $rest : $articles; @endphp
            @forelse($articlesToLoop as $index => $article)
                @php
                    $gradients = [
                        'linear-gradient(135deg,#0d9488,#0f766e)',
                        'linear-gradient(135deg,#2563eb,#1d4ed8)',
                        'linear-gradient(135deg,#f97316,#ea580c)',
                        'linear-gradient(135deg,#7c3aed,#6d28d9)',
                        'linear-gradient(135deg,#059669,#047857)',
                        'linear-gradient(135deg,#d97706,#b45309)'
                    ];
                    $bgGrad = $gradients[$index % count($gradients)];
                @endphp
                <a class="card cc" href="{{ route('seo.articles.show', $article->slug) }}">
                    <div class="th" style="background:{{ $bgGrad }}">
                        @if($article->featured_image_path)
                            <img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" style="object-position: {{ $article->image_position ?? '50% 50%' }}" loading="lazy">
                        @else
                            <svg class="i"><use href="#pen"/></svg>
                        @endif
                        <span class="bd">{{ !empty($article->keywords) ? $article->keywords[0] : 'آموزشی' }}</span>
                    </div>
                    <div class="cb">
                        <h3>{{ $article->title }}</h3>
                        <p style="font-size:13px;color:var(--mute);line-height:1.7;margin:0 0 12px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $article->excerpt }}</p>
                        <div class="mt">
                            <span><svg class="i" style="width:13px;height:13px;vertical-align:middle;margin-left:3px"><use href="#clock"/></svg>{{ $article->published_at ? $article->published_at->format('Y/m/d') : '' }}</span>
                            <span>{{ $article->author->name ?? 'شیخان' }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <div class="card" style="grid-column: 1 / -1; padding: 48px; text-align: center; color: var(--mute)">
                    <p style="font-size: 16px; margin-bottom: 12px">مقاله‌ای با این مشخصات یافت نشد.</p>
                    <a class="btn s" href="{{ route('seo.articles.index') }}">مشاهده همه مقالات</a>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div style="margin-top:36px">
            {{ $articles->links() }}
        </div>


    </div>
@endsection
