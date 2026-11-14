@extends('admin.layouts.app')

@section('page-title', 'مدیریت بنرها')
@section('page-description', 'مدیریت تصاویر تبلیغاتی، محل نمایش، بازه زمانی و اولویت بنرها')

@push('head')
    @vite(['resources/css/admin/seo.css'])
@endpush

@section('content')
<div class="seo-admin">
    <header class="seo-admin-hero">
        <div><span class="seo-admin-status">مدیریت رسانه</span><h2>بنرهای سایت</h2><p>برای هر بنر تصویر، متن جایگزین، لینک مقصد، محل نمایش، ترتیب و بازه‌ی فعال‌بودن را مشخص کن.</p></div>
        <div class="seo-admin-hero-actions"><a class="panel-btn panel-btn-secondary" href="{{ route('admin.seo.index') }}">بازگشت به مرکز سئو</a><a class="panel-btn" href="{{ route('seo.articles.index') }}" target="_blank" rel="noopener">مشاهده محل نمایش</a></div>
    </header>

    @if($errors->any())
        <div class="seo-admin-errors" role="alert"><strong>بنر ذخیره نشد:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="seo-admin-kpis">
        <article class="seo-admin-kpi"><span>کل بنرها</span><strong>{{ number_format($banners->total()) }}</strong><small>در فهرست فعلی</small></article>
        <article class="seo-admin-kpi"><span>فعال در این بازه</span><strong>{{ number_format($activeBannerCount) }}</strong><small>قابل نمایش برای بازدیدکننده</small></article>
        <article class="seo-admin-kpi"><span>پیش‌نویس</span><strong>{{ number_format($draftBannerCount) }}</strong><small>منتشرنشده</small></article>
        <article class="seo-admin-kpi"><span>غیرفعال</span><strong>{{ number_format($inactiveBannerCount) }}</strong><small>نمایش داده نمی‌شوند</small></article>
    </section>

    <form class="seo-admin-section" method="POST" action="{{ route('admin.seo.banners.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="seo-admin-section-head"><div><h3>ثبت بنر جدید</h3><p>تصویر فقط پس از اعتبارسنجی ذخیره می‌شود؛ متن جایگزین برای دسترس‌پذیری و سئو الزامی است.</p></div><span class="seo-admin-status">فرم ایجاد</span></div>
        <div class="panel-form-grid panel-form-grid-3">
            <div class="panel-field"><label for="banner-title">عنوان داخلی *</label><input id="banner-title" class="panel-input" name="title" required maxlength="180" value="{{ old('title') }}"></div>
            <div class="panel-field"><label for="banner-alt">متن جایگزین تصویر (Alt) *</label><input id="banner-alt" class="panel-input" name="alt_text" required maxlength="180" value="{{ old('alt_text') }}"></div>
            <div class="panel-field"><label for="banner-image">فایل تصویر *</label><input id="banner-image" class="panel-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required><small class="seo-admin-field-hint">JPG، PNG یا WebP؛ حداکثر ۴ مگابایت.</small></div>
            <div class="panel-field"><label for="banner-placement">محل نمایش *</label><select id="banner-placement" class="panel-select" name="placement" required><option value="home_hero" @selected(old('placement') === 'home_hero')>اسلایدر صفحه اصلی</option><option value="articles_top" @selected(old('placement', 'articles_top') === 'articles_top')>بالای فهرست مقاله‌ها</option><option value="articles_sidebar" @selected(old('placement') === 'articles_sidebar')>کنار صفحه مقاله</option><option value="sitewide" @selected(old('placement') === 'sitewide')>عمومی در بخش مقاله‌ها</option></select></div>
            <div class="panel-field"><label for="banner-target">لینک مقصد</label><input id="banner-target" class="panel-input" name="target_url" maxlength="2048" value="{{ old('target_url') }}" dir="ltr" placeholder="/articles یا https://..."></div>
            <div class="panel-field"><label for="banner-order">ترتیب نمایش *</label><input id="banner-order" class="panel-input" type="number" min="0" max="100000" name="sort_order" value="{{ old('sort_order', 0) }}" required></div>
            <div class="panel-field"><label for="banner-start">شروع نمایش</label><input id="banner-start" class="panel-input" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></div>
            <div class="panel-field"><label for="banner-end">پایان نمایش</label><input id="banner-end" class="panel-input" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"></div>
            <div class="panel-field"><label for="banner-status">وضعیت *</label><select id="banner-status" class="panel-select" name="status" required><option value="draft" @selected(old('status', 'draft') === 'draft')>پیش‌نویس</option><option value="active" @selected(old('status') === 'active')>فعال</option><option value="inactive" @selected(old('status') === 'inactive')>غیرفعال</option></select></div>
        </div>
        <label class="seo-admin-check"><input type="checkbox" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab'))><span><strong>بازکردن لینک در زبانه جدید</strong><br><small>برای لینک خارجی از ویژگی امن noopener استفاده می‌شود.</small></span></label>
        <div class="seo-admin-actions"><button class="panel-btn" type="submit">ثبت بنر</button><button class="panel-btn panel-btn-secondary" type="reset">پاک‌کردن فرم</button></div>
    </form>

    <section class="seo-admin-section">
        <div class="seo-admin-section-head"><div><h3>فهرست بنرها</h3><p>بنر فقط در صورت فعال‌بودن و قرارگرفتن در بازه‌ی زمانی معتبر نمایش داده می‌شود.</p></div></div>
        <div class="seo-admin-toolbar"><input class="panel-input seo-admin-search" type="search" placeholder="جست‌وجو در عنوان بنر..." data-seo-filter="seo-banners-table"><span class="panel-muted">{{ number_format($banners->total()) }} بنر</span></div>
        <div class="panel-table-wrap"><table class="panel-table" id="seo-banners-table"><thead><tr><th>تصویر</th><th>عنوان و محل</th><th>اولویت</th><th>بازه نمایش</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
        @forelse($banners as $banner)
            <tr data-seo-row data-search="{{ mb_strtolower($banner->title.' '.$banner->alt_text) }}">
                <td><img class="seo-admin-thumb" src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" loading="lazy"></td>
                <td><strong>{{ $banner->title }}</strong><div class="panel-muted">{{ ['home_hero'=>'اسلایدر صفحه اصلی','articles_top'=>'بالای فهرست مقاله‌ها','articles_sidebar'=>'کنار مقاله','sitewide'=>'عمومی در بخش مقاله‌ها'][$banner->placement] ?? $banner->placement }}</div></td>
                <td>{{ number_format($banner->sort_order) }}</td>
                <td><span class="panel-muted">{{ $banner->starts_at?->format('Y/m/d H:i') ?? 'بدون شروع' }}</span><br><span class="panel-muted">{{ $banner->ends_at?->format('Y/m/d H:i') ?? 'بدون پایان' }}</span></td>
                <td><span class="seo-admin-status {{ $banner->status === 'active' && (!$banner->starts_at || $banner->starts_at->lte(now())) && (!$banner->ends_at || $banner->ends_at->gte(now())) ? 'is-live' : 'is-muted' }}">{{ ['draft'=>'پیش‌نویس','active'=>'فعال','inactive'=>'غیرفعال'][$banner->status] ?? $banner->status }}</span></td>
                <td>
                    <details>
                        <summary class="panel-btn panel-btn-secondary panel-btn-sm">مدیریت بنر</summary>
                        <div class="seo-admin-inline-form">
                            <form method="POST" action="{{ route('admin.seo.banners.update', $banner) }}" enctype="multipart/form-data">
                                @csrf @method('PATCH')
                                <div class="panel-form-grid">
                                    <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $banner->title }}" required maxlength="180"></div>
                                    <div class="panel-field"><label>متن جایگزین</label><input class="panel-input" name="alt_text" value="{{ $banner->alt_text }}" required maxlength="180"></div>
                                    <div class="panel-field"><label>تصویر جدید (اختیاری)</label><input class="panel-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"><div class="seo-admin-image-preview"><img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}"><span class="panel-muted">تصویر فعلی</span></div></div>
                                    <div class="panel-field"><label>محل نمایش</label><select class="panel-select" name="placement">@foreach(['home_hero'=>'اسلایدر صفحه اصلی','articles_top'=>'بالای فهرست مقاله‌ها','articles_sidebar'=>'کنار مقاله','sitewide'=>'عمومی در بخش مقاله‌ها'] as $key=>$label)<option value="{{ $key }}" @selected($banner->placement === $key)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="panel-field"><label>لینک مقصد</label><input class="panel-input" name="target_url" value="{{ $banner->target_url }}" dir="ltr" maxlength="2048"></div>
                                    <div class="panel-field"><label>ترتیب نمایش</label><input class="panel-input" type="number" name="sort_order" min="0" max="100000" value="{{ $banner->sort_order }}" required></div>
                                    <div class="panel-field"><label>شروع نمایش</label><input class="panel-input" type="datetime-local" name="starts_at" value="{{ $banner->starts_at?->format('Y-m-d\\TH:i') }}"></div>
                                    <div class="panel-field"><label>پایان نمایش</label><input class="panel-input" type="datetime-local" name="ends_at" value="{{ $banner->ends_at?->format('Y-m-d\\TH:i') }}"></div>
                                    <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach(['draft'=>'پیش‌نویس','active'=>'فعال','inactive'=>'غیرفعال'] as $key=>$label)<option value="{{ $key }}" @selected($banner->status === $key)>{{ $label }}</option>@endforeach</select></div>
                                </div>
                                <label class="seo-admin-check"><input type="checkbox" name="open_in_new_tab" value="1" @checked($banner->open_in_new_tab)><span>بازکردن لینک در زبانه جدید</span></label>
                                <button class="panel-btn" type="submit">ذخیره تغییرات</button>
                            </form>
                            <form method="POST" action="{{ route('admin.seo.banners.destroy', $banner) }}" data-confirm="این بنر و فایل تصویر آن حذف شود؟">@csrf @method('DELETE')<button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف بنر و تصویر</button></form>
                        </div>
                    </details>
                </td>
            </tr>
        @empty<tr><td colspan="6" class="panel-empty">هنوز بنری ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div>
        <div class="panel-pagination"><span>{{ $banners->count() }} از {{ $banners->total() }} بنر</span>{{ $banners->links() }}</div>
    </section>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-seo-filter]').forEach(input => {
        const table = document.getElementById(input.dataset.seoFilter);
        input.addEventListener('input', () => {
            const term = input.value.trim().toLocaleLowerCase();
            table?.querySelectorAll('[data-seo-row]').forEach(row => {
                row.hidden = term !== '' && !row.dataset.search.includes(term);
            });
        });
    });
});
</script>
@endpush
@endsection
