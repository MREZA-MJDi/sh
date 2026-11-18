@extends('admin.layouts.app')
@section('title', 'مدیریت سئو')

@section('content')
<x-admin.page-head tone="shop" icon="search" title="مرکز مدیریت سئو" desc="کنترل عنوان‌ها، متادیتا، ساختار محتوا، خزنده‌ها و نمایش برند در نتایج جست‌وجو"/>

<div class="adm-stats">
  <x-admin.stat tone="shop" icon="file" label="مقاله‌ها" :value="number_format($articleCount)"/>
  <x-admin.stat tone="shop" icon="check" label="مقاله‌های منتشرشده" :value="number_format($publishedArticleCount)"/>
  <x-admin.stat tone="shop" icon="edit" label="پیش‌نویس / زمان‌بندی" :value="number_format($draftArticleCount)"/>
  <x-admin.stat tone="shop" icon="image" label="بنرهای فعال" :value="number_format($activeBannerCount).' / '.number_format($bannerCount)"/>
</div>

<p class="adm-muted">محتوای متن‌محور صفحه اصلی، صفحه دوره‌ها، مدرسان، تماس، سبد خرید، وبلاگ و فوتر از فرم تنظیمات همین صفحه کنترل می‌شود. بنرها و مقاله‌های وبلاگ از بخش‌های مستقل مدیریت می‌شوند.</p>
<div class="adm-actions" style="margin-bottom:18px">
  <x-admin.btn icon="file" :href="route('admin.seo.articles.index')">مدیریت مقاله‌ها</x-admin.btn>
  <x-admin.btn variant="line" icon="image" :href="route('admin.seo.banners.index')">مدیریت بنرها</x-admin.btn>
  <x-admin.btn variant="line" icon="star" :href="route('admin.seo.testimonials.index')">نظرات صفحه اصلی</x-admin.btn>
  <x-admin.btn variant="line" icon="external-link" :href="route('seo.robots')">مشاهده robots.txt</x-admin.btn>
</div>

<x-admin.panel title="پیش‌نمایش نتیجه جست‌وجو" desc="نمونه عنوان و توضیح پیش‌فرض در موتورهای جست‌وجو" data-tone="shop">
  <div class="adm-seo-preview">
    <small>{{ $settings->canonical_base_url ?: config('app.url') }}</small>
    <h3>{{ $settings->default_title }}{{ $settings->title_separator }}{{ $settings->site_name }}</h3>
    <p>{{ $settings->default_description }}</p>
  </div>
</x-admin.panel>

<x-admin.panel title="تنظیمات عمومی سئو" desc="مقادیر پیش‌فرض متادیتا و هویت سازمانی" flush>
  <form method="POST" action="{{ route('admin.seo.settings.update') }}" enctype="multipart/form-data" class="adm-form">
    @csrf @method('PATCH')
    <x-admin.legend title="عنوان و توضیحات پیش‌فرض"/>
    <x-admin.input label="نام وب‌سایت" name="site_name" :value="$settings->site_name" required/>
    <x-admin.input label="عنوان پیش‌فرض" name="default_title" :value="$settings->default_title" required/>
    <x-admin.input label="جداکننده عنوان" name="title_separator" :value="$settings->title_separator" required/>
    <x-admin.textarea label="توضیحات پیش‌فرض" name="default_description" :value="$settings->default_description" wide/>
    <x-admin.input label="کلمات کلیدی (با کاما جدا کنید)" name="default_keywords" :value="implode(', ', $settings->default_keywords ?? [])" wide/>
    <x-admin.input label="نشانی پایه canonical" name="canonical_base_url" type="url" :value="$settings->canonical_base_url" wide/>

    <x-admin.legend title="هویت سازمانی و شبکه‌های اجتماعی"/>
    <x-admin.input label="نام سازمان" name="organization_name" :value="$settings->organization_name"/>
    <x-admin.input label="وب‌سایت سازمان" name="organization_url" type="url" :value="$settings->organization_url"/>
    <x-admin.input label="شناسه X / Twitter" name="twitter_handle" :value="$settings->twitter_handle"/>
    <x-admin.input label="کد Google Search Console" name="google_site_verification" :value="$settings->google_site_verification"/>
    <x-admin.input label="کد Bing Webmaster" name="bing_site_verification" :value="$settings->bing_site_verification"/>
    <x-admin.input label="تصویر Open Graph پیش‌فرض" name="default_og_image" type="file" accept="image/jpeg,image/png,image/webp"/>
    <x-admin.input label="لوگوی سازمان" name="organization_logo" type="file" accept="image/jpeg,image/png,image/webp"/>
    <x-admin.input label="نشان اعتماد" name="trust_badge" type="file" accept="image/jpeg,image/png,image/webp"/>
    <x-admin.textarea label="توضیح فوتر" name="footer_description" :value="$settings->footer_description" wide/>


    <x-admin.legend title="عنوان و توضیحات صفحات عمومی"/>
    <x-admin.input label="عنوان سئوی صفحه اصلی" name="home_sections[home_meta_title]" :value="data_get($settings->home_sections, 'home_meta_title')" maxlength="180" wide/>
    <x-admin.textarea label="توضیحات سئوی صفحه اصلی" name="home_sections[home_meta_description]" :value="data_get($settings->home_sections, 'home_meta_description')" wide/>
    <x-admin.input label="عنوان صفحه مقالات" name="home_sections[blog_page_title]" :value="data_get($settings->home_sections, 'blog_page_title')" maxlength="180"/>
    <x-admin.textarea label="توضیحات صفحه مقالات" name="home_sections[blog_page_description]" :value="data_get($settings->home_sections, 'blog_page_description')"/>
    <x-admin.input label="عنوان صفحه دوره‌ها" name="home_sections[courses_page_title]" :value="data_get($settings->home_sections, 'courses_page_title')" maxlength="180"/>
    <x-admin.textarea label="توضیحات صفحه دوره‌ها" name="home_sections[courses_page_description]" :value="data_get($settings->home_sections, 'courses_page_description')"/>
    <x-admin.input label="عنوان صفحه مدرسان" name="home_sections[teachers_page_title]" :value="data_get($settings->home_sections, 'teachers_page_title')" maxlength="180"/>
    <x-admin.textarea label="توضیحات صفحه مدرسان" name="home_sections[teachers_page_description]" :value="data_get($settings->home_sections, 'teachers_page_description')"/>
    <x-admin.input label="عنوان صفحه تماس" name="home_sections[contact_page_title]" :value="data_get($settings->home_sections, 'contact_page_title')" maxlength="180"/>
    <x-admin.textarea label="توضیحات صفحه تماس" name="home_sections[contact_page_description]" :value="data_get($settings->home_sections, 'contact_page_description')" wide/>

    @include('admin.seo.partials.public-content-fields')

    <x-admin.legend title="شبکه‌های اجتماعی و لینک‌های فوتر"/>
    <x-admin.input label="Instagram" name="social_links[instagram]" type="url" :value="data_get($settings->social_links, 'instagram')"/>
    <x-admin.input label="Telegram" name="social_links[telegram]" type="url" :value="data_get($settings->social_links, 'telegram')"/>
    <x-admin.input label="LinkedIn" name="social_links[linkedin]" type="url" :value="data_get($settings->social_links, 'linkedin')"/>
    <x-admin.input label="X" name="social_links[x]" type="url" :value="data_get($settings->social_links, 'x')"/>
    <x-admin.input label="YouTube" name="social_links[youtube]" type="url" :value="data_get($settings->social_links, 'youtube')"/>
    @php($seoFooterLinks = $settings->footer_links ?? [])
    @foreach (range(0, 3) as $footerIndex)
      <x-admin.input label="عنوان لینک فوتر {{ $footerIndex + 1 }}" name="footer_links[{{ $footerIndex }}][label]" :value="$seoFooterLinks[$footerIndex]['label'] ?? ''"/>
      <x-admin.input label="نشانی لینک فوتر {{ $footerIndex + 1 }}" name="footer_links[{{ $footerIndex }}][url]" :value="$seoFooterLinks[$footerIndex]['url'] ?? ''" wide/>
    @endforeach

    <x-admin.legend title="کنترل خزنده‌ها و نقشه سایت"/>
    <x-admin.switch label="اجازه ایندکس صفحات عمومی" name="robots_index" :checked="$settings->robots_index"/>
    <x-admin.switch label="اجازه دنبال‌کردن لینک‌ها" name="robots_follow" :checked="$settings->robots_follow"/>
    <x-admin.switch label="فعال بودن sitemap.xml" name="sitemap_enabled" :checked="$settings->sitemap_enabled"/>
    <div class="wide"><button type="submit" class="adm-btn adm-btn-primary">ذخیره تنظیمات سئو</button></div>
  </form>
</x-admin.panel>

<div class="adm-split">
  <x-admin.panel title="آخرین مقاله‌ها" desc="محتوای تازه و وضعیت انتشار">
    @forelse($recentArticles as $article)
      <div class="adm-session"><div class="adm-grow"><b>{{ $article->title }}</b><div class="adm-muted">{{ $article->status }} · {{ $article->updated_at?->format('Y/m/d') }}</div></div><x-admin.btn size="sm" variant="line" :href="route('admin.seo.articles.index')">مدیریت</x-admin.btn></div>
    @empty
      <p class="adm-muted">مقاله‌ای ثبت نشده است.</p>
    @endforelse
  </x-admin.panel>
  <x-admin.panel title="آخرین بنرها" desc="بنرهای صفحه اصلی و صفحات محتوا">
    @forelse($recentBanners as $banner)
      <div class="adm-session"><div class="adm-grow"><b>{{ $banner->title }}</b><div class="adm-muted">{{ $banner->placement }} · {{ $banner->status }}</div></div><x-admin.btn size="sm" variant="line" :href="route('admin.seo.banners.index')">مدیریت</x-admin.btn></div>
    @empty
      <p class="adm-muted">بنری ثبت نشده است.</p>
    @endforelse
  </x-admin.panel>
</div>
@endsection
