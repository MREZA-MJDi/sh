@extends('admin.layouts.app')

@section('page-title', 'مدیریت مقاله‌ها')
@section('page-description', 'مدیریت چرخه انتشار، محتوای مقاله و تمام داده‌های فنی سئو')

@push('head')
    @vite(['resources/css/admin/seo.css'])
@endpush

@section('content')
<div class="seo-admin">
    <header class="seo-admin-hero">
        <div><span class="seo-admin-status">مدیریت محتوای متنی</span><h2>مقاله‌ها و محتوای سئو</h2><p>مقاله بساز، محتوای غنی و تصاویر را مدیریت کن و عنوان، توضیح، canonical و زمان انتشار را تنظیم کن.</p></div>
        <div class="seo-admin-hero-actions"><a class="panel-btn panel-btn-secondary" href="{{ route('admin.seo.index') }}">بازگشت به مرکز سئو</a><a class="panel-btn" href="{{ route('seo.articles.index') }}" target="_blank" rel="noopener">مشاهده صفحه عمومی</a></div>
    </header>

    @if($errors->any())
        <div class="seo-admin-errors" role="alert"><strong>مقاله ذخیره نشد:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="seo-admin-kpis">
        <article class="seo-admin-kpi"><span>کل مقاله‌ها</span><strong>{{ number_format($articles->total()) }}</strong><small>در فهرست فعلی</small></article>
        <article class="seo-admin-kpi"><span>منتشرشده</span><strong>{{ number_format($publishedArticleCount) }}</strong><small>در صفحه عمومی قابل مشاهده</small></article>
        <article class="seo-admin-kpi"><span>پیش‌نویس</span><strong>{{ number_format($draftArticleCount) }}</strong><small>منتشرنشده</small></article>
        <article class="seo-admin-kpi"><span>زمان‌بندی‌شده</span><strong>{{ number_format($scheduledArticleCount) }}</strong><small>در انتظار زمان انتشار</small></article>
    </section>

    <form class="seo-admin-section" method="POST" action="{{ route('admin.seo.articles.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="seo-admin-section-head"><div><h3>ایجاد مقاله جدید</h3><p>فیلدهای دارای ستاره الزامی‌اند. محتوای HTML پیش از ذخیره‌سازی پاک‌سازی می‌شود.</p></div><span class="seo-admin-status">فرم ایجاد</span></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="seo-article-title">عنوان مقاله *</label><input id="seo-article-title" class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
            <div class="panel-field"><label for="seo-article-slug">نشانی کوتاه (Slug)</label><input id="seo-article-slug" class="panel-input" name="slug" value="{{ old('slug') }}" maxlength="190" dir="ltr" placeholder="article-url"><small class="seo-admin-field-hint">در صورت خالی‌بودن، از عنوان ساخته می‌شود.</small></div>
            <div class="panel-field"><label for="seo-article-excerpt">خلاصه مقاله</label><textarea id="seo-article-excerpt" class="panel-textarea" name="excerpt" data-rich-text-ignore maxlength="500">{{ old('excerpt') }}</textarea></div>
            <div class="panel-field"><label for="seo-article-body">متن کامل مقاله *</label><textarea id="seo-article-body" class="panel-textarea seo-admin-richtext" name="body" data-rich-text required maxlength="100000">{{ old('body') }}</textarea><small class="seo-admin-field-hint">از تیترها، فهرست‌ها، لینک‌ها، جدول و تصویرهای درون‌متنی مجاز استفاده کن.</small></div>
        </div>

        <div class="seo-admin-section-head"><div><h3>تصاویر و دسترس‌پذیری</h3><p>تصویر شاخص برای صفحه مقاله و تصویر OG برای اشتراک‌گذاری؛ متن جایگزین برای تصویر شاخص الزامی است.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="seo-featured-image">تصویر شاخص</label><input id="seo-featured-image" class="panel-input" type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"><small class="seo-admin-field-hint">JPG، PNG یا WebP؛ حداکثر ۵ مگابایت.</small></div>
            <div class="panel-field"><label for="seo-image-alt">متن جایگزین تصویر (Alt)</label><input id="seo-image-alt" class="panel-input" name="image_alt" value="{{ old('image_alt') }}" maxlength="180"></div>
            <div class="panel-field"><label for="seo-og-image">تصویر Open Graph</label><input id="seo-og-image" class="panel-input" type="file" name="og_image" accept="image/jpeg,image/png,image/webp"><small class="seo-admin-field-hint">پیشنهاد: ۱۲۰۰×۶۳۰ پیکسل.</small></div>
        </div>

        <div class="seo-admin-section-head"><div><h3>تنظیمات اختصاصی موتور جست‌وجو</h3><p>در صورت خالی‌بودن عنوان یا توضیح، از عنوان مقاله و خلاصه یا تنظیمات پیش‌فرض سایت استفاده می‌شود.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="seo-meta-title">عنوان متا</label><input id="seo-meta-title" class="panel-input" name="meta_title" value="{{ old('meta_title') }}" maxlength="180"></div>
            <div class="panel-field"><label for="seo-meta-description">توضیح متا</label><textarea id="seo-meta-description" class="panel-textarea" name="meta_description" data-rich-text-ignore maxlength="320">{{ old('meta_description') }}</textarea><div class="seo-admin-counter"><span>توضیح متا</span><span data-count-for="meta_description">0 / 320</span></div></div>
            <div class="panel-field"><label for="seo-keywords">کلیدواژه‌ها</label><input id="seo-keywords" class="panel-input" name="keywords" value="{{ old('keywords') }}" maxlength="1000"><small class="seo-admin-field-hint">با ویرگول فارسی یا انگلیسی جدا شوند.</small></div>
            <div class="panel-field"><label for="seo-canonical">Canonical اختصاصی</label><input id="seo-canonical" class="panel-input" name="canonical_url" type="url" value="{{ old('canonical_url') }}" dir="ltr" maxlength="2048" placeholder="https://example.com/articles/..."></div>
        </div>

        <div class="seo-admin-section-head"><div><h3>انتشار مقاله</h3><p>مقاله زمان‌بندی‌شده فقط پس از رسیدن زمان انتشار در صفحه عمومی و نقشه سایت قرار می‌گیرد.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label for="seo-status">وضعیت</label><select id="seo-status" class="panel-select" name="status" required><option value="draft" @selected(old('status', 'draft') === 'draft')>پیش‌نویس</option><option value="published" @selected(old('status') === 'published')>انتشار فوری</option><option value="scheduled" @selected(old('status') === 'scheduled')>زمان‌بندی‌شده</option><option value="archived" @selected(old('status') === 'archived')>بایگانی</option></select></div>
            <div class="panel-field"><label for="seo-published-at">زمان انتشار</label><input id="seo-published-at" class="panel-input" type="datetime-local" name="published_at" value="{{ old('published_at') }}"><small class="seo-admin-field-hint">برای وضعیت زمان‌بندی‌شده، تاریخ آینده الزامی است.</small></div>
        </div>
        <div class="seo-admin-actions"><button class="panel-btn" type="submit">ذخیره مقاله</button><button class="panel-btn panel-btn-secondary" type="reset">پاک‌کردن فرم</button></div>
    </form>

    <section class="seo-admin-section">
        <div class="seo-admin-section-head"><div><h3>فهرست مقاله‌ها</h3><p>ویرایش، مشاهده نسخه عمومی یا حذف مقاله و فایل‌های وابسته.</p></div></div>
        <div class="seo-admin-toolbar"><input class="panel-input seo-admin-search" type="search" placeholder="جست‌وجو در عنوان یا نشانی مقاله..." data-seo-filter="seo-articles-table"><span class="panel-muted">{{ number_format($articles->total()) }} مقاله</span></div>
        <div class="panel-table-wrap"><table class="panel-table" id="seo-articles-table"><thead><tr><th>مقاله</th><th>نشانی</th><th>نویسنده</th><th>وضعیت</th><th>تاریخ انتشار</th><th>عملیات</th></tr></thead><tbody>
        @forelse($articles as $article)
            <tr data-seo-row data-search="{{ mb_strtolower($article->title.' '.$article->slug) }}">
                <td><strong>{{ $article->title }}</strong><div class="panel-muted">{{ \Illuminate\Support\Str::limit($article->excerpt, 75) }}</div></td>
                <td dir="ltr">{{ $article->slug }}</td>
                <td>{{ $article->author?->name ?? '—' }}</td>
                <td><span class="seo-admin-status {{ $article->status === 'published' && $article->published_at?->isPast() ? 'is-live' : 'is-muted' }}">{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','scheduled'=>'زمان‌بندی‌شده','archived'=>'بایگانی'][$article->status] ?? $article->status }}</span></td>
                <td>{{ $article->published_at?->format('Y/m/d H:i') ?? '—' }}</td>
                <td>
                    <details>
                        <summary class="panel-btn panel-btn-secondary panel-btn-sm">مدیریت مقاله</summary>
                        <div class="seo-admin-inline-form">
                            <form method="POST" action="{{ route('admin.seo.articles.update', $article) }}" enctype="multipart/form-data">
                                @csrf @method('PATCH')
                                <div class="panel-form-grid">
                                    <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $article->title }}" required maxlength="180"></div>
                                    <div class="panel-field"><label>Slug</label><input class="panel-input" name="slug" value="{{ $article->slug }}" dir="ltr" maxlength="190"></div>
                                    <div class="panel-field"><label>خلاصه</label><textarea class="panel-textarea" name="excerpt" data-rich-text-ignore maxlength="500">{{ $article->excerpt }}</textarea></div>
                                    <div class="panel-field"><label>متن مقاله</label><textarea class="panel-textarea seo-admin-richtext" name="body" data-rich-text required maxlength="100000">{{ $article->body }}</textarea></div>
                                    <div class="panel-field"><label>تصویر شاخص جدید</label><input class="panel-input" type="file" name="featured_image" accept="image/jpeg,image/png,image/webp">@if($article->featured_image_path)<div class="seo-admin-image-preview"><img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}"><span class="panel-muted">تصویر فعلی</span></div>@endif</div>
                                    <div class="panel-field"><label>متن جایگزین</label><input class="panel-input" name="image_alt" value="{{ $article->image_alt }}" maxlength="180"></div>
                                    <div class="panel-field"><label>عنوان متا</label><input class="panel-input" name="meta_title" value="{{ $article->meta_title }}" maxlength="180"></div>
                                    <div class="panel-field"><label>توضیح متا</label><textarea class="panel-textarea" name="meta_description" data-rich-text-ignore maxlength="320">{{ $article->meta_description }}</textarea></div>
                                    <div class="panel-field"><label>کلیدواژه‌ها</label><input class="panel-input" name="keywords" value="{{ implode(', ', $article->keywords ?? []) }}" maxlength="1000"></div>
                                    <div class="panel-field"><label>Canonical</label><input class="panel-input" name="canonical_url" type="url" dir="ltr" value="{{ $article->canonical_url }}" maxlength="2048"></div>
                                    <div class="panel-field"><label>تصویر OG جدید</label><input class="panel-input" type="file" name="og_image" accept="image/jpeg,image/png,image/webp">@if($article->og_image_path)<div class="seo-admin-image-preview"><img src="{{ Storage::disk('public')->url($article->og_image_path) }}" alt="تصویر اشتراک‌گذاری مقاله"><span class="panel-muted">تصویر فعلی</span></div>@endif</div>
                                    <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach(['draft'=>'پیش‌نویس','published'=>'منتشرشده','scheduled'=>'زمان‌بندی‌شده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($article->status === $key)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="panel-field"><label>زمان انتشار</label><input class="panel-input" type="datetime-local" name="published_at" value="{{ $article->published_at?->format('Y-m-d\\TH:i') }}"></div>
                                </div>
                                <button class="panel-btn" type="submit">ذخیره تغییرات مقاله</button>
                            </form>
                            <div class="seo-admin-actions">
                                @if($article->status === 'published' && $article->published_at?->isPast())<a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('seo.articles.show', $article->slug) }}" target="_blank" rel="noopener">مشاهده عمومی</a>@endif
                                <form method="POST" action="{{ route('admin.seo.articles.destroy', $article) }}" data-confirm="مقاله و تصاویر وابسته حذف شوند؟">@csrf @method('DELETE')<button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف مقاله</button></form>
                            </div>
                        </div>
                    </details>
                </td>
            </tr>
        @empty<tr><td colspan="6" class="panel-empty">هنوز مقاله‌ای ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div>
        <div class="panel-pagination"><span>{{ $articles->count() }} از {{ $articles->total() }} مقاله</span>{{ $articles->links() }}</div>
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
    document.querySelectorAll('[data-count-for]').forEach(counter => {
        const field = document.querySelector('[name="' + counter.dataset.countFor + '"]');
        const update = () => { counter.textContent = (field?.value.length || 0) + ' / ' + field.maxLength; };
        field?.addEventListener('input', update); update();
    });
});
</script>
@endpush
@endsection
