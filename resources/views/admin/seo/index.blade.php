@extends('admin.layouts.app')

@section('page-title', 'مدیریت سئو')
@section('page-description', 'تنظیمات نمایه‌سازی، اطلاعات پیش‌فرض صفحات و وضعیت محتوای عمومی')

@section('content')
    <div class="panel-grid panel-grid-4">
        <div class="panel-card panel-stat"><span class="panel-stat-label">کل مقاله‌ها</span><strong class="panel-stat-value">{{ number_format($articleCount) }}</strong><span class="panel-stat-note">محتوای ثبت‌شده</span></div>
        <div class="panel-card panel-stat"><span class="panel-stat-label">مقاله‌های قابل انتشار</span><strong class="panel-stat-value">{{ number_format($publishedArticleCount) }}</strong><span class="panel-stat-note">منتشرشده و قابل ایندکس</span></div>
        <div class="panel-card panel-stat"><span class="panel-stat-label">پیش‌نویس و زمان‌بندی</span><strong class="panel-stat-value">{{ number_format($draftArticleCount) }}</strong><span class="panel-stat-note">نیازمند بررسی یا انتشار</span></div>
        <div class="panel-card panel-stat"><span class="panel-stat-label">بنرهای فعال</span><strong class="panel-stat-value">{{ number_format($activeBannerCount) }}</strong><span class="panel-stat-note">{{ number_format($bannerCount) }} بنر ثبت‌شده</span></div>
    </div>

    <div class="panel-card">
        <div class="panel-card-title"><div><h3>بخش‌های مدیریت محتوا</h3><p>عملیات واقعی روی پایگاه داده؛ بدون داده آماری نمایشی</p></div></div>
        <div class="panel-form-actions">
            <a class="panel-btn" href="{{ route('admin.seo.articles.index') }}">مدیریت مقاله‌ها</a>
            <a class="panel-btn panel-btn-secondary" href="{{ route('admin.seo.banners.index') }}">مدیریت بنرها</a>
            <a class="panel-btn panel-btn-secondary" href="{{ route('seo.articles.index') }}" target="_blank" rel="noopener">مشاهده صفحه عمومی مقاله‌ها</a>
            <a class="panel-btn panel-btn-secondary" href="{{ route('seo.sitemap') }}" target="_blank" rel="noopener">مشاهده نقشه سایت</a>
        </div>
    </div>

    <form class="panel-card" method="POST" action="{{ route('admin.seo.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <div class="panel-card-title"><div><h3>تنظیمات اصلی سئو</h3><p>مقادیر پیش‌فرض برای صفحات عمومی؛ عنوان و توضیح اختصاصی مقاله اولویت دارد.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="site_name">نام وب‌سایت</label><input id="site_name" class="panel-input" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required maxlength="160"></div>
            <div class="panel-field"><label for="default_title">عنوان پیش‌فرض صفحه</label><input id="default_title" class="panel-input" name="default_title" value="{{ old('default_title', $settings->default_title) }}" required maxlength="180"></div>
            <div class="panel-field"><label for="title_separator">جداکننده عنوان</label><input id="title_separator" class="panel-input" name="title_separator" value="{{ old('title_separator', $settings->title_separator) }}" required maxlength="10"></div>
            <div class="panel-field"><label for="canonical_base_url">نشانی اصلی سایت (Canonical Base)</label><input id="canonical_base_url" class="panel-input" type="url" name="canonical_base_url" value="{{ old('canonical_base_url', $settings->canonical_base_url) }}" placeholder="https://example.com"></div>
            <div class="panel-field" style="grid-column:1/-1"><label for="default_description">توضیح پیش‌فرض (Meta Description)</label><textarea id="default_description" class="panel-textarea" name="default_description" maxlength="500">{{ old('default_description', $settings->default_description) }}</textarea></div>
            <div class="panel-field" style="grid-column:1/-1"><label for="default_keywords">کلیدواژه‌ها، با ویرگول جدا شوند</label><input id="default_keywords" class="panel-input" name="default_keywords" value="{{ old('default_keywords', implode(', ', $settings->default_keywords ?? [])) }}" maxlength="1000"></div>
            <div class="panel-field"><label for="organization_name">نام سازمان برای داده ساختاریافته</label><input id="organization_name" class="panel-input" name="organization_name" value="{{ old('organization_name', $settings->organization_name) }}" maxlength="180"></div>
            <div class="panel-field"><label for="organization_url">نشانی سازمان</label><input id="organization_url" class="panel-input" type="url" name="organization_url" value="{{ old('organization_url', $settings->organization_url) }}"></div>
            <div class="panel-field"><label for="twitter_handle">شناسه X / Twitter</label><input id="twitter_handle" class="panel-input" name="twitter_handle" value="{{ old('twitter_handle', $settings->twitter_handle) }}" placeholder="@username"></div>
            <div class="panel-field"><label for="google_site_verification">کد تأیید Google Search Console</label><input id="google_site_verification" class="panel-input" name="google_site_verification" value="{{ old('google_site_verification', $settings->google_site_verification) }}"></div>
            <div class="panel-field"><label for="bing_site_verification">کد تأیید Bing Webmaster</label><input id="bing_site_verification" class="panel-input" name="bing_site_verification" value="{{ old('bing_site_verification', $settings->bing_site_verification) }}"></div>
            <div class="panel-field"><label for="default_og_image">تصویر پیش‌فرض شبکه‌های اجتماعی</label><input id="default_og_image" class="panel-input" type="file" name="default_og_image" accept="image/jpeg,image/png,image/webp">@if($settings->default_og_image_path)<span class="panel-muted">تصویر فعلی: {{ $settings->default_og_image_path }}</span>@endif</div>
            <div class="panel-field"><label for="organization_logo">لوگوی سازمان</label><input id="organization_logo" class="panel-input" type="file" name="organization_logo" accept="image/jpeg,image/png,image/webp">@if($settings->organization_logo_path)<span class="panel-muted">لوگوی فعلی: {{ $settings->organization_logo_path }}</span>@endif</div>
        </div>
        <div class="panel-grid panel-grid-3">
            <label class="panel-checkbox"><input type="checkbox" name="robots_index" value="1" @checked(old('robots_index', $settings->robots_index))> اجازه ایندکس صفحات عمومی</label>
            <label class="panel-checkbox"><input type="checkbox" name="robots_follow" value="1" @checked(old('robots_follow', $settings->robots_follow))> دنبال‌کردن لینک‌ها</label>
            <label class="panel-checkbox"><input type="checkbox" name="sitemap_enabled" value="1" @checked(old('sitemap_enabled', $settings->sitemap_enabled))> فعال‌بودن sitemap.xml</label>
        </div>
        <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره تنظیمات سئو</button></div>
    </form>

    <div class="panel-grid panel-grid-2">
        <section class="panel-card"><div class="panel-card-title"><div><h3>آخرین مقاله‌ها</h3><p>آخرین تغییرات محتوایی</p></div><a href="{{ route('admin.seo.articles.index') }}">همه مقاله‌ها</a></div>
            @forelse($recentArticles as $article)<div class="panel-list-row"><div class="panel-list-main"><strong>{{ $article->title }}</strong><span>{{ $article->status }} · {{ $article->updated_at?->format('Y/m/d H:i') }}</span></div></div>@empty<p class="panel-empty">هنوز مقاله‌ای ثبت نشده است.</p>@endforelse
        </section>
        <section class="panel-card"><div class="panel-card-title"><div><h3>آخرین بنرها</h3><p>ترتیب و وضعیت نمایش</p></div><a href="{{ route('admin.seo.banners.index') }}">همه بنرها</a></div>
            @forelse($recentBanners as $banner)<div class="panel-list-row"><div class="panel-list-main"><strong>{{ $banner->title }}</strong><span>{{ $banner->placement }} · {{ $banner->status }} · اولویت {{ $banner->sort_order }}</span></div></div>@empty<p class="panel-empty">هنوز بنری ثبت نشده است.</p>@endforelse
        </section>
    </div>
@endsection
