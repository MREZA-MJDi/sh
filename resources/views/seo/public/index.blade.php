@extends('seo.public.layout')

@section('seo-title', 'مقاله‌ها')
@section('og-type', 'website')

@section('content')
    <section class="seo-intro"><span>دانش و یادگیری</span><h1>مجله شیخان</h1><p>{{ $settings->default_description }}</p></section>
    <form method="GET" action="{{ route('seo.articles.index') }}" class="ac-card" style="display:flex;gap:10px;flex-wrap:wrap;padding:16px;margin-bottom:22px">
        <input class="ac-field" style="flex:1;min-width:200px;margin:0" type="search" name="q" value="{{ $search }}" placeholder="جستجوی عنوان یا خلاصه مقاله">
        <select class="ac-field" style="width:auto;min-width:170px;margin:0" name="keyword"><option value="">همه موضوع‌ها</option>@foreach($keywords as $item)<option value="{{ $item }}" @selected($keyword===$item)>{{ $item }}</option>@endforeach</select>
        <button class="ac-btn" type="submit">جستجو</button>
        <a class="ac-btn ac-btn-outline" href="{{ route('seo.articles.index') }}">پاک‌کردن</a>
    </form>
    @foreach($banners as $banner)
        <div class="seo-banner">
            @if($banner->target_url)<a href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>@endif
                <img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" loading="lazy">
            @if($banner->target_url)</a>@endif
        </div>
    @endforeach
    <section class="seo-article-grid">
        @forelse($articles as $article)
            <article class="seo-article-card">
                @if($article->featured_image_path)<a href="{{ route('seo.articles.show', $article->slug) }}"><img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" loading="lazy"></a>@endif
                <div class="seo-article-card-body">
                    <time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->format('Y/m/d') }}</time>
                    <h2><a href="{{ route('seo.articles.show', $article->slug) }}">{{ $article->title }}</a></h2>
                    <p>{{ $article->excerpt }}</p>
                    <a class="seo-read-more" href="{{ route('seo.articles.show', $article->slug) }}">ادامه مطلب</a>
                </div>
            </article>
        @empty
            <div class="seo-empty">هنوز مقاله عمومی منتشر نشده است.</div>
        @endforelse
    </section>
    {{ $articles->links() }}
@endsection
