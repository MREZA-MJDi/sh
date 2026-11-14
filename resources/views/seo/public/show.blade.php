@extends('public.layouts.academy')

@section('title', ($article->meta_title ?: $article->title) . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'شیخان'))
@section('description', $article->meta_description ?: $article->excerpt ?: $settings->default_description)
@section('canonical', $article->canonical_url ?: route('seo.articles.show', $article->slug))

@push('head')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $article->meta_title ?: $article->title }}">
    <meta property="og:description" content="{{ $article->meta_description ?: $article->excerpt ?: $settings->default_description }}">
    <meta property="og:url" content="{{ $article->canonical_url ?: route('seo.articles.show', $article->slug) }}">
    <meta property="article:published_time" content="{{ $article->published_at ? $article->published_at->toAtomString() : now()->toAtomString() }}">
    <meta property="article:modified_time" content="{{ $article->updated_at ? $article->updated_at->toAtomString() : now()->toAtomString() }}">
    @if($article->author)<meta name="author" content="{{ $article->author->name }}">@endif
    @if($article->keywords)<meta name="keywords" content="{{ implode(', ', $article->keywords) }}">@endif
    @php
        $articleImage = $article->og_image_path ?: $article->featured_image_path;
        $imageUrl = $articleImage ? Storage::disk('public')->url($articleImage) : ($settings->default_og_image_path ? Storage::disk('public')->url($settings->default_og_image_path) : null);
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->meta_title ?: $article->title,
            'description' => $article->meta_description ?: $article->excerpt,
            'datePublished' => $article->published_at ? $article->published_at->toAtomString() : now()->toAtomString(),
            'dateModified' => $article->updated_at ? $article->updated_at->toAtomString() : now()->toAtomString(),
            'author' => [
                '@type' => 'Person',
                'name' => $article->author?->name ?: ($settings->organization_name ?: $settings->site_name)
            ],
            'image' => $imageUrl,
            'mainEntityOfPage' => $article->canonical_url ?: route('seo.articles.show', $article->slug),
            'publisher' => [
                '@type' => 'Organization',
                'name' => $settings->organization_name ?: $settings->site_name,
                'url' => $settings->organization_url ?: rtrim($settings->canonical_base_url ?: url('/'), '/'),
                'logo' => $settings->organization_logo_path ? Storage::disk('public')->url($settings->organization_logo_path) : null,
            ],
        ];
    @endphp
    @if($imageUrl)
        <meta property="og:image" content="{{ $imageUrl }}">
        <meta name="twitter:image" content="{{ $imageUrl }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@php
    $body = $article->body ?? '';
    $headings = [];
    if (preg_match_all('/<h2([^>]*)>(.*?)<\/h2>/iu', $body, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $index => $match) {
            $attrs = $match[1];
            $text = strip_tags($match[2]);
            if (preg_match('/id=["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
                $id = $idMatch[1];
            } else {
                $id = 's' . ($index + 1);
                $body = preg_replace('/' . preg_quote($match[0], '/') . '/', '<h2 id="' . $id . '"' . $attrs . '>' . $match[2] . '</h2>', $body, 1);
            }
            $headings[] = ['id' => $id, 'title' => $text];
        }
    }
    $readingMinutes = max(3, (int) ceil(mb_strlen(strip_tags($body), 'UTF-8') / 700));
@endphp

@section('content')
    {{-- Top Header Section --}}
    <div class="pgh">
        <div class="c">
            <div class="bc">
                <a href="{{ route('home') }}">خانه</a> ‹
                <a href="{{ route('seo.articles.index') }}">مقالات</a> ‹
                <span>{{ !empty($article->keywords) ? $article->keywords[0] : 'یادگیری' }}</span>
            </div>
            <h1>{{ $article->title }}</h1>
            <div class="mt" style="color:#cbd5f1">
                <span>
                    <svg class="i" style="width:14px;height:14px;vertical-align:middle;margin-left:4px"><use href="#clock"/></svg>
                    {{ $article->published_at ? $article->published_at->format('Y/m/d') : 'امروز' }}
                </span>
                <span>
                    <svg class="i" style="width:14px;height:14px;vertical-align:middle;margin-left:4px"><use href="#clock"/></svg>
                    {{ $readingMinutes }} دقیقه مطالعه
                </span>
                <span>
                    <svg class="i" style="width:14px;height:14px;vertical-align:middle;margin-left:4px"><use href="#user"/></svg>
                    نویسنده: {{ $article->author->name ?? ($settings->site_name ?? 'شیخان') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Main Article & Sticky Sidebar Layout --}}
    <div class="c">
        <div class="lay-post">
            {{-- Main Article Card --}}
            <article class="card art">
                {{-- Cover Image or Gradient --}}
                @if($article->featured_image_path)
                    <div class="th">
                        <img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" loading="lazy">
                    </div>
                @else
                    <div class="th" style="background:linear-gradient(135deg,#0d9488,#2563eb)">
                        <svg class="i"><use href="#pen"/></svg>
                    </div>
                @endif

                {{-- Lead / Summary --}}
                @if($article->excerpt)
                    <p class="art-lead">{{ $article->excerpt }}</p>
                @endif

                {{-- Rendered Body with H2 IDs for TOC --}}
                <div class="art-body">
                    {!! $body !!}
                </div>

                {{-- Keyword Tags --}}
                @if(!empty($article->keywords))
                    <div class="art-tags">
                        <span style="font-size:13px;color:var(--mute);margin-left:4px">برچسب‌ها:</span>
                        @foreach($article->keywords as $kw)
                            <a class="chip" href="{{ route('seo.articles.index', ['keyword' => $kw]) }}"># {{ $kw }}</a>
                        @endforeach
                    </div>
                @endif

                {{-- Author Bio Card --}}
                <div class="auth card">
                    <div class="av">{{ mb_substr($article->author->name ?? 'ش', 0, 1, 'UTF-8') }}</div>
                    <div>
                        <b>{{ $article->author->name ?? ($settings->site_name ?? 'شیخان') }}</b>
                        <p style="margin:0;color:var(--mute)">مدرس و مشاور تخصصی آکادمی شیخان</p>
                    </div>
                </div>

                {{-- Comments Section --}}
                <div class="cmt-sec" id="comments">
                    <h3>دیدگاه‌ها</h3>
                    <div class="cmt-box">
                        <textarea class="inp" id="cmt-text" rows="3" placeholder="دیدگاه خود را بنویسید…"></textarea>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px">
                            <small style="color:var(--mute)">دیدگاه شما پس از بررسی نمایش داده خواهد شد.</small>
                            <button type="button" class="btn s" id="cmt-submit">ارسال دیدگاه</button>
                        </div>
                        <div id="cmt-msg" style="display:none;margin-top:12px;padding:12px 16px;background:var(--soft);color:var(--pri);border-radius:10px;font-size:14px;font-weight:600">
                            دیدگاه شما با موفقیت ثبت شد و پس از بررسی منتشر خواهد شد.
                        </div>
                    </div>

                    {{-- Sample approved comment to demonstrate look and feel --}}
                    <div class="cmt-item">
                        <div class="av">س</div>
                        <div class="cb">
                            <div>
                                <b>سارا احمدی</b>
                                <time>۲ روز پیش</time>
                            </div>
                            <p>نکات مربوط به زمان شناور و ایستگاه‌های مرور فاصله‌دار فوق‌العاده کاربردی بود. ممنون از آموزشگاه شیخان.</p>
                        </div>
                    </div>
                </div>
            </article>

            {{-- Sidebar Aside --}}
            <aside>
                <div class="card flt toc">
                    <b class="toc-title">
                        <svg class="i"><use href="#brief"/></svg>
                        فهرست مطالب
                    </b>
                    @if(count($headings) > 0)
                        <nav class="toc-nav">
                            @foreach($headings as $h)
                                <a href="#{{ $h['id'] }}">{{ $h['title'] }}</a>
                            @endforeach
                        </nav>
                    @else
                        <p style="font-size:13px;color:var(--mute);margin:0">مطالعه کامل این مقاله حدود {{ $readingMinutes }} دقیقه زمان می‌برد.</p>
                    @endif

                    {{-- Related Articles --}}
                    @if(isset($relatedArticles) && $relatedArticles->isNotEmpty())
                        <h4 class="toc-sub">
                            <svg class="i" style="width:16px;height:16px;color:var(--pri)"><use href="#pen"/></svg>
                            مقالات مرتبط
                        </h4>
                        <div>
                            @foreach($relatedArticles as $rel)
                                <a class="rel-art-item" href="{{ route('seo.articles.show', $rel->slug) }}">
                                    <div class="rel-th">
                                        @if($rel->featured_image_path)
                                            <img src="{{ Storage::disk('public')->url($rel->featured_image_path) }}" alt="{{ $rel->title }}" loading="lazy">
                                        @else
                                            <svg class="i"><use href="#pen"/></svg>
                                        @endif
                                    </div>
                                    <div class="rel-cb">
                                        <h5>{{ $rel->title }}</h5>
                                        <time>{{ $rel->published_at ? $rel->published_at->format('Y/m/d') : '' }}</time>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Recommended Related Course --}}
                    @if(isset($relatedCourses) && $relatedCourses->isNotEmpty())
                        @php $promo = $relatedCourses->first(); @endphp
                        <div class="toc-promo">
                            <span class="bd" style="font-size:10px;padding:3px 8px;margin-bottom:6px;display:inline-block">دوره پیشنهادی</span>
                            <h5>{{ $promo->title }}</h5>
                            <p>{{ $promo->teacher->name ?? 'اساتید مجرب شیخان' }}</p>
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px">
                                <b style="font-size:13px;color:var(--ink)">{{ number_format($promo->price) }} تومان</b>
                                <a class="btn s" style="font-size:12px;padding:6px 12px" href="{{ route('public.courses.show', $promo->slug) }}">مشاهده</a>
                            </div>
                        </div>
                    @endif

                    {{-- Sidebar Banners if configured --}}
                    @if(isset($banners) && $banners->isNotEmpty())
                        <div style="margin-top:18px">
                            @foreach($banners as $banner)
                                <div style="margin-bottom:12px;border-radius:12px;overflow:hidden">
                                    @if($banner->target_url)<a href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>@endif
                                        <img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" style="width:100%;height:auto;display:block" loading="lazy">
                                    @if($banner->target_url)</a>@endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const cmtSubmit = document.getElementById('cmt-submit');
    const cmtText = document.getElementById('cmt-text');
    const cmtMsg = document.getElementById('cmt-msg');
    if (cmtSubmit && cmtText && cmtMsg) {
        cmtSubmit.addEventListener('click', function () {
            if (!cmtText.value.trim()) {
                cmtText.focus();
                return;
            }
            cmtMsg.style.display = 'block';
            cmtText.value = '';
        });
    }

    // Smooth scroll for TOC links
    document.querySelectorAll('.toc-nav a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>
@endpush
