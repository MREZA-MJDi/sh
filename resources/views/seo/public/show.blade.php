@extends('seo.public.layout')

@section('seo-title', $article->meta_title ?: $article->title)
@section('seo-description', $article->meta_description ?: $article->excerpt ?: $settings->default_description)
@section('seo-keywords', implode(', ', $article->keywords ?? $settings->default_keywords ?? []))
@section('seo-canonical', $article->canonical_url ?: route('seo.articles.show', $article->slug))
@section('seo-image', $article->og_image_path ? Storage::disk('public')->url($article->og_image_path) : ($article->featured_image_path ? Storage::disk('public')->url($article->featured_image_path) : ($settings->default_og_image_path ? Storage::disk('public')->url($settings->default_og_image_path) : '')))
@section('og-type', 'article')

@push('head')
    <meta property="article:published_time" content="{{ $article->published_at->toAtomString() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at->toAtomString() }}">
    @if($article->author)<meta name="author" content="{{ $article->author->name }}">@endif
    <script type="application/ld+json">@json([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => $article->meta_description ?: $article->excerpt,
        'datePublished' => $article->published_at->toAtomString(),
        'dateModified' => $article->updated_at->toAtomString(),
        'author' => ['@type' => 'Person', 'name' => $article->author?->name ?: $settings->organization_name ?: $settings->site_name],
        'image' => $article->featured_image_path ? Storage::disk('public')->url($article->featured_image_path) : null,
        'mainEntityOfPage' => route('seo.articles.show', $article->slug),
        'publisher' => ['@type' => 'Organization', 'name' => $settings->organization_name ?: $settings->site_name],
    ])</script>
@endpush

@section('content')
    <nav class="seo-breadcrumb"><a href="{{ route('seo.articles.index') }}">مقاله‌ها</a><span> / </span><span>{{ $article->title }}</span></nav>
    <article class="seo-article">
        @if($article->featured_image_path)<img class="seo-article-cover" src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}">@endif
        <header class="seo-article-header"><time datetime="{{ $article->published_at->toAtomString() }}">{{ $article->published_at->format('Y/m/d') }}</time><h1>{{ $article->title }}</h1>@if($article->excerpt)<p>{{ $article->excerpt }}</p>@endif</header>
        <div class="seo-article-body">{!! $article->body !!}</div>
    </article>
    @if($banners->isNotEmpty())
        <aside class="seo-related-banners">@foreach($banners as $banner)<div class="seo-banner">@if($banner->target_url)<a href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>@endif<img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" loading="lazy">@if($banner->target_url)</a>@endif</div>@endforeach</aside>
    @endif
@endsection
