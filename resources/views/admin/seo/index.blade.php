@extends('admin.layouts.app')

@section('page-title', 'مدیریت سئو')
@section('page-description', 'مرکز کنترل محتوای قابل جست‌وجو، نمایه‌سازی و هویت دیجیتال آکادمی')

@push('head')
    @vite(['resources/css/admin/seo.css'])
@endpush

@section('content')
<div class="seo-admin">
    <header class="seo-admin-hero">
        <div>
            <span class="seo-admin-status {{ $settings->robots_index ? 'is-live' : 'is-muted' }}">{{ $settings->robots_index ? 'نمایه‌سازی عمومی فعال' : 'نمایه‌سازی عمومی غیرفعال' }}</span>
            <h2>مرکز مدیریت سئو</h2>
            <p>تنظیم هویت سایت در موتورهای جست‌وجو، مدیریت مقاله‌ها و بنرها و کنترل خروجی‌های فنی.</p>
        </div>
        <div class="seo-admin-hero-actions">
            <a class="panel-btn" href="{{ route('admin.seo.articles.index') }}">مدیریت مقاله‌ها</a>
            <a class="panel-btn panel-btn-secondary" href="{{ route('admin.seo.banners.index') }}">مدیریت بنرها</a>
            <a class="panel-btn panel-btn-secondary" href="{{ route('seo.articles.index') }}" target="_blank" rel="noopener">مشاهده خروجی عمومی</a>
        </div>
    </header>

    @if($errors->any())
        <div class="seo-admin-errors" role="alert">
            <strong>اطلاعات ذخیره نشد؛ موارد زیر را بررسی کن:</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="seo-admin-kpis" aria-label="خلاصه وضعیت سئو">
        <article class="seo-admin-kpi"><span>کل مقاله‌ها</span><strong>{{ number_format($articleCount) }}</strong><small>تمام وضعیت‌ها</small></article>
        <article class="seo-admin-kpi"><span>مقاله‌های عمومی</span><strong>{{ number_format($publishedArticleCount) }}</strong><small>قابل مشاهده برای بازدیدکننده</small></article>
        <article class="seo-admin-kpi"><span>پیش‌نویس و زمان‌بندی</span><strong>{{ number_format($draftArticleCount) }}</strong><small>نیازمند انتشار یا بازبینی</small></article>
        <article class="seo-admin-kpi"><span>بنرهای فعال</span><strong>{{ number_format($activeBannerCount) }}</strong><small>از {{ number_format($bannerCount) }} بنر ثبت‌شده</small></article>
    </section>

    <section class="seo-admin-section">
        <div class="seo-admin-section-head">
            <div><h3>پیش‌نمایش نتیجه جست‌وجو</h3><p>نمونه‌ی عنوان، نشانی و توضیحی که برای صفحات عمومی این بخش تولید می‌شود.</p></div>
            <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('seo.robots') }}" target="_blank" rel="noopener">مشاهده robots.txt</a>
        </div>
        <div class="seo-admin-preview">
            <span class="seo-admin-preview-label">پیش‌نمایش نتیجه جست‌وجو</span>
            <strong class="seo-admin-preview-title" data-seo-preview-title>{{ $settings->default_title }}{{ $settings->title_separator }}{{ $settings->site_name }}</strong>
            <span class="seo-admin-preview-url" data-seo-preview-url>{{ rtrim($settings->canonical_base_url ?: url('/'), '/') }}/articles</span>
            <p class="seo-admin-preview-description" data-seo-preview-description>{{ $settings->default_description }}</p>
        </div>
    </section>

    <form class="seo-admin-section" method="POST" action="{{ route('admin.seo.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="seo-admin-section-head">
            <div><h3>تنظیمات پایه و متادیتا</h3><p>این مقادیر پیش‌فرض صفحات عمومی مقاله‌ها هستند؛ تنظیمات اختصاصی هر مقاله اولویت دارد.</p></div>
            <span class="seo-admin-status">تنظیمات سراسری</span>
        </div>

        <div class="panel-form-grid">
            <div class="panel-field"><label for="site_name">نام وب‌سایت</label><input id="site_name" class="panel-input" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required maxlength="160" data-seo-site-name></div>
            <div class="panel-field"><label for="default_title">عنوان پیش‌فرض صفحه</label><input id="default_title" class="panel-input" name="default_title" value="{{ old('default_title', $settings->default_title) }}" required maxlength="180" data-seo-title></div>
            <div class="panel-field"><label for="title_separator">جداکننده عنوان</label><input id="title_separator" class="panel-input" name="title_separator" value="{{ old('title_separator', $settings->title_separator) }}" required maxlength="10" data-seo-separator></div>
            <div class="panel-field"><label for="canonical_base_url">نشانی اصلی سایت (Canonical Base)</label><input id="canonical_base_url" class="panel-input" type="url" name="canonical_base_url" value="{{ old('canonical_base_url', $settings->canonical_base_url) }}" placeholder="https://example.com" dir="ltr"><small class="seo-admin-field-hint">فقط دامنه‌ی اصلی سایت؛ بدون مسیر صفحه.</small></div>
            <div class="panel-field"><label for="default_description">توضیح پیش‌فرض (Meta Description)</label><textarea id="default_description" class="panel-textarea" name="default_description" data-rich-text-ignore maxlength="500" data-seo-description>{{ old('default_description', $settings->default_description) }}</textarea><div class="seo-admin-counter"><span>توضیح نتیجه جست‌وجو</span><span data-count-for="default_description">0 / 500</span></div></div>
            <div class="panel-field"><label for="default_keywords">کلیدواژه‌های پیش‌فرض</label><input id="default_keywords" class="panel-input" name="default_keywords" value="{{ old('default_keywords', implode(', ', $settings->default_keywords ?? [])) }}" maxlength="1000"><small class="seo-admin-field-hint">با ویرگول فارسی یا انگلیسی جدا کن؛ از تکرار واژه‌ها پرهیز شود.</small></div>
        </div>

        <div class="seo-admin-section-head"><div><h3>هویت سازمان و تصاویر اشتراک‌گذاری</h3><p>برای داده‌های ساختاریافته و کارت پیش‌نمایش شبکه‌های اجتماعی.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="organization_name">نام سازمان</label><input id="organization_name" class="panel-input" name="organization_name" value="{{ old('organization_name', $settings->organization_name) }}" maxlength="180"></div>
            <div class="panel-field"><label for="organization_url">نشانی سازمان</label><input id="organization_url" class="panel-input" type="url" name="organization_url" value="{{ old('organization_url', $settings->organization_url) }}" dir="ltr"></div>
            <div class="panel-field"><label for="default_og_image">تصویر پیش‌فرض Open Graph</label><input id="default_og_image" class="panel-input" type="file" name="default_og_image" accept="image/jpeg,image/png,image/webp"><small class="seo-admin-field-hint">ترجیحاً تصویر ۱۲۰۰×۶۳۰ پیکسل، حداکثر ۴ مگابایت.</small>
                @if($settings->default_og_image_path)<div class="seo-admin-image-preview"><img src="{{ Storage::disk('public')->url($settings->default_og_image_path) }}" alt="تصویر پیش‌فرض شبکه‌های اجتماعی"><span class="panel-muted">تصویر فعلی ذخیره شده است.</span></div>@endif
            </div>
            <div class="panel-field"><label for="organization_logo">لوگوی سازمان</label><input id="organization_logo" class="panel-input" type="file" name="organization_logo" accept="image/jpeg,image/png,image/webp"><small class="seo-admin-field-hint">تصویر واضح و مربعی برای معرفی ناشر.</small>
                @if($settings->organization_logo_path)<div class="seo-admin-image-preview"><img src="{{ Storage::disk('public')->url($settings->organization_logo_path) }}" alt="لوگوی سازمان"><span class="panel-muted">لوگوی فعلی ذخیره شده است.</span></div>@endif
            </div>
        </div>

        <div class="seo-admin-section-head"><div><h3>شبکه‌های اجتماعی</h3><p>نشانی رسمی پروفایل‌ها برای داده‌های ناشر و اشتراک‌گذاری.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="twitter_handle">شناسه X / Twitter</label><input id="twitter_handle" class="panel-input" name="twitter_handle" value="{{ old('twitter_handle', $settings->twitter_handle) }}" placeholder="@username" dir="ltr"></div>
            <div class="panel-field"><label for="social_instagram">اینستاگرام</label><input id="social_instagram" class="panel-input" type="url" name="social_links[instagram]" value="{{ old('social_links.instagram', $settings->social_links['instagram'] ?? '') }}" placeholder="https://instagram.com/..." dir="ltr"></div>
            <div class="panel-field"><label for="social_telegram">تلگرام</label><input id="social_telegram" class="panel-input" type="url" name="social_links[telegram]" value="{{ old('social_links.telegram', $settings->social_links['telegram'] ?? '') }}" placeholder="https://t.me/..." dir="ltr"></div>
            <div class="panel-field"><label for="social_linkedin">لینکدین</label><input id="social_linkedin" class="panel-input" type="url" name="social_links[linkedin]" value="{{ old('social_links.linkedin', $settings->social_links['linkedin'] ?? '') }}" placeholder="https://linkedin.com/..." dir="ltr"></div>
        </div>

        <div class="seo-admin-section-head"><div><h3>تأیید مالکیت موتورهای جست‌وجو</h3><p>کدها بدون تغییر در بخش head صفحات عمومی درج می‌شوند.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="google_site_verification">کد Google Search Console</label><input id="google_site_verification" class="panel-input" name="google_site_verification" value="{{ old('google_site_verification', $settings->google_site_verification) }}" dir="ltr" autocomplete="off"></div>
            <div class="panel-field"><label for="bing_site_verification">کد Bing Webmaster</label><input id="bing_site_verification" class="panel-input" name="bing_site_verification" value="{{ old('bing_site_verification', $settings->bing_site_verification) }}" dir="ltr" autocomplete="off"></div>
        </div>

        <div class="seo-admin-section-head"><div><h3>کنترل خزنده‌ها و نقشه سایت</h3><p>این گزینه‌ها روی meta robots و خروجی robots.txt و sitemap.xml اثر می‌گذارند.</p></div></div>
        <div class="seo-admin-checks">
            <label class="seo-admin-check"><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index', $settings->robots_index))><span><strong>اجازه ایندکس</strong><br><small>صفحات عمومی قابل ثبت در نتایج جست‌وجو باشند.</small></span></label>
            <label class="seo-admin-check"><input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow', $settings->robots_follow))><span><strong>دنبال‌کردن لینک‌ها</strong><br><small>خزنده‌ها لینک‌های موجود در صفحه را دنبال کنند.</small></span></label>
            <label class="seo-admin-check"><input type="checkbox" name="sitemap_enabled" value="1" @checked(old('sitemap_enabled', $settings->sitemap_enabled))><span><strong>فعال‌بودن نقشه سایت</strong><br><small>فقط مقاله‌های منتشرشده در sitemap.xml قرار بگیرند.</small></span></label>
        </div>

        <div class="seo-admin-actions seo-admin-save-actions">
            <button class="panel-btn" type="submit">ذخیره همه تنظیمات سئو</button>
            <a class="panel-btn panel-btn-secondary" href="{{ route('seo.sitemap') }}" target="_blank" rel="noopener">بازکردن sitemap.xml</a>
        </div>
    </form>

    <div class="panel-grid panel-grid-2">
        <section class="seo-admin-section">
            <div class="seo-admin-section-head"><div><h3>آخرین مقاله‌ها</h3><p>وضعیت انتشار و زمان آخرین تغییر.</p></div><a href="{{ route('admin.seo.articles.index') }}" class="panel-btn panel-btn-secondary panel-btn-sm">مدیریت همه</a></div>
            @forelse($recentArticles as $article)
                <div class="seo-admin-list-item"><div><strong>{{ $article->title }}</strong><small>{{ $article->slug }} · {{ $article->updated_at?->format('Y/m/d H:i') }}</small></div><span class="seo-admin-status {{ $article->status === 'published' && $article->published_at?->isPast() ? 'is-live' : 'is-muted' }}">{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','scheduled'=>'زمان‌بندی‌شده','archived'=>'بایگانی'][$article->status] ?? $article->status }}</span></div>
            @empty<p class="panel-empty">هنوز مقاله‌ای ثبت نشده است.</p>@endforelse
        </section>
        <section class="seo-admin-section">
            <div class="seo-admin-section-head"><div><h3>آخرین بنرها</h3><p>محل قرارگیری، اولویت و وضعیت فعلی.</p></div><a href="{{ route('admin.seo.banners.index') }}" class="panel-btn panel-btn-secondary panel-btn-sm">مدیریت همه</a></div>
            @forelse($recentBanners as $banner)
                <div class="seo-admin-list-item"><div><strong>{{ $banner->title }}</strong><small>{{ ['articles_top'=>'بالای فهرست مقاله‌ها','articles_sidebar'=>'کنار مقاله','sitewide'=>'صفحه مقاله‌ها'][$banner->placement] ?? $banner->placement }} · اولویت {{ $banner->sort_order }}</small></div><span class="seo-admin-status {{ $banner->status === 'active' && (!$banner->starts_at || $banner->starts_at->isPast()) && (!$banner->ends_at || $banner->ends_at->isFuture()) ? 'is-live' : 'is-muted' }}">{{ ['draft'=>'پیش‌نویس','active'=>'فعال','inactive'=>'غیرفعال'][$banner->status] ?? $banner->status }}</span></div>
            @empty<p class="panel-empty">هنوز بنری ثبت نشده است.</p>@endforelse
        </section>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const title = document.querySelector('[data-seo-title]');
    const site = document.querySelector('[data-seo-site-name]');
    const separator = document.querySelector('[data-seo-separator]');
    const description = document.querySelector('[data-seo-description]');
    const previewTitle = document.querySelector('[data-seo-preview-title]');
    const previewDescription = document.querySelector('[data-seo-preview-description]');
    const previewUrl = document.querySelector('[data-seo-preview-url]');
    const canonical = document.querySelector('[name="canonical_base_url"]');
    const update = () => {
        if (previewTitle) previewTitle.textContent = [title?.value, site?.value].filter(Boolean).join(separator?.value || ' | ');
        if (previewDescription) previewDescription.textContent = description?.value || '';
        if (previewUrl) previewUrl.textContent = (canonical?.value || window.location.origin).replace(/\/$/, '') + '/articles';
        const counter = document.querySelector('[data-count-for="default_description"]');
        if (counter && description) counter.textContent = description.value.length + ' / 500';
    };
    [title, site, separator, description, canonical].forEach(field => field?.addEventListener('input', update));
    update();
});
</script>
@endpush
@endsection
