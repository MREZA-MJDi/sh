@extends('admin.layouts.app')

@section('page-title', 'مدیریت مقاله‌ها')
@section('page-description', 'ایجاد و ویرایش مقاله، تنظیمات متا، تصویر شاخص و انتشار')

@section('content')
    <div class="panel-page-head"><div><h2>مقاله‌های سایت</h2><p>{{ number_format($articles->total()) }} مقاله ثبت‌شده</p></div><a class="panel-btn panel-btn-secondary" href="{{ route('admin.seo.index') }}">بازگشت به سئو</a></div>
    <form class="panel-card" method="POST" action="{{ route('admin.seo.articles.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel-card-title"><div><h3>ایجاد مقاله</h3><p>مقاله‌های منتشرشده در صفحه عمومی و نقشه سایت قرار می‌گیرند.</p></div></div>
        <div class="panel-form-grid">
            <div class="panel-field"><label>عنوان مقاله</label><input class="panel-input" name="title" required maxlength="180"></div>
            <div class="panel-field"><label>نشانی کوتاه (Slug)</label><input class="panel-input" name="slug" maxlength="190" dir="ltr" placeholder="optional-article-slug"></div>
            <div class="panel-field" style="grid-column:1/-1"><label>خلاصه مقاله</label><textarea class="panel-textarea" name="excerpt" maxlength="500"></textarea></div>
            <div class="panel-field" style="grid-column:1/-1"><label>متن مقاله</label><textarea class="panel-textarea" name="body" data-rich-text required maxlength="100000"></textarea></div>
            <div class="panel-field"><label>تصویر شاخص</label><input class="panel-input" type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"></div>
            <div class="panel-field"><label>متن جایگزین تصویر</label><input class="panel-input" name="image_alt" maxlength="180"></div>
            <div class="panel-field"><label>عنوان سئو</label><input class="panel-input" name="meta_title" maxlength="180"></div>
            <div class="panel-field"><label>توضیح متا</label><textarea class="panel-textarea" name="meta_description" maxlength="320"></textarea></div>
            <div class="panel-field"><label>کلیدواژه‌ها (با ویرگول)</label><input class="panel-input" name="keywords" maxlength="1000"></div>
            <div class="panel-field"><label>Canonical اختصاصی</label><input class="panel-input" name="canonical_url" type="url" dir="ltr" maxlength="2048"></div>
            <div class="panel-field"><label>تصویر Open Graph (اختیاری)</label><input class="panel-input" type="file" name="og_image" accept="image/jpeg,image/png,image/webp"></div>
            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">انتشار فوری</option><option value="scheduled">زمان‌بندی</option><option value="archived">بایگانی</option></select></div>
            <div class="panel-field"><label>زمان انتشار</label><input class="panel-input" type="datetime-local" name="published_at"></div>
        </div>
        <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره مقاله</button></div>
    </form>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>مقاله‌های ثبت‌شده</h3><p>وضعیت، نشانی عمومی و عملیات ویرایش و حذف</p></div></div>
        <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>عنوان</th><th>Slug</th><th>نویسنده</th><th>وضعیت</th><th>انتشار</th><th>عملیات</th></tr></thead><tbody>
        @forelse($articles as $article)
            <tr>
                <td><strong>{{ $article->title }}</strong><div class="panel-muted">{{ \Illuminate\Support\Str::limit($article->excerpt, 90) }}</div></td>
                <td dir="ltr">{{ $article->slug }}</td>
                <td>{{ $article->author?->name ?? '—' }}</td>
                <td><span class="panel-badge {{ $article->status === 'published' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','scheduled'=>'زمان‌بندی‌شده','archived'=>'بایگانی'][$article->status] ?? $article->status }}</span></td>
                <td>{{ $article->published_at?->format('Y/m/d H:i') ?? '—' }}</td>
                <td><details><summary class="panel-btn panel-btn-secondary panel-btn-sm">ویرایش</summary>
                    <form method="POST" action="{{ route('admin.seo.articles.update', $article) }}" enctype="multipart/form-data" class="panel-card">
                        @csrf @method('PATCH')
                        <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $article->title }}" required maxlength="180"></div>
                        <div class="panel-field"><label>Slug</label><input class="panel-input" name="slug" value="{{ $article->slug }}" dir="ltr" maxlength="190"></div>
                        <div class="panel-field"><label>خلاصه</label><textarea class="panel-textarea" name="excerpt" maxlength="500">{{ $article->excerpt }}</textarea></div>
                        <div class="panel-field"><label>متن مقاله</label><textarea class="panel-textarea" name="body" data-rich-text required maxlength="100000">{{ $article->body }}</textarea></div>
                        <div class="panel-field"><label>تصویر شاخص جدید (اختیاری)</label><input class="panel-input" type="file" name="featured_image" accept="image/jpeg,image/png,image/webp"></div>
                        <div class="panel-field"><label>متن جایگزین</label><input class="panel-input" name="image_alt" value="{{ $article->image_alt }}" maxlength="180"></div>
                        <div class="panel-field"><label>عنوان متا</label><input class="panel-input" name="meta_title" value="{{ $article->meta_title }}" maxlength="180"></div>
                        <div class="panel-field"><label>توضیح متا</label><textarea class="panel-textarea" name="meta_description" maxlength="320">{{ $article->meta_description }}</textarea></div>
                        <div class="panel-field"><label>کلیدواژه‌ها</label><input class="panel-input" name="keywords" value="{{ implode(', ', $article->keywords ?? []) }}"></div>
                        <div class="panel-field"><label>Canonical</label><input class="panel-input" name="canonical_url" type="url" dir="ltr" value="{{ $article->canonical_url }}"></div>
                        <div class="panel-field"><label>تصویر OG جدید (اختیاری)</label><input class="panel-input" type="file" name="og_image" accept="image/jpeg,image/png,image/webp"></div>
                        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach(['draft'=>'پیش‌نویس','published'=>'منتشرشده','scheduled'=>'زمان‌بندی‌شده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($article->status===$key)>{{ $label }}</option>@endforeach</select></div>
                        <div class="panel-field"><label>زمان انتشار</label><input class="panel-input" type="datetime-local" name="published_at" value="{{ $article->published_at?->format('Y-m-d\TH:i') }}"></div>
                        <button class="panel-btn" type="submit">ذخیره تغییرات</button>
                    </form>
                    @if($article->status === 'published' && $article->published_at?->isPast())<a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('seo.articles.show', $article->slug) }}" target="_blank" rel="noopener">مشاهده عمومی</a>@endif
                    <form method="POST" action="{{ route('admin.seo.articles.destroy', $article) }}" data-confirm="مقاله و تصاویر وابسته حذف شوند؟">@csrf @method('DELETE')<button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف مقاله</button></form>
                </details></td>
            </tr>
        @empty<tr><td colspan="6" class="panel-empty">هنوز مقاله‌ای ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div>
        <div class="panel-pagination"><span>{{ $articles->count() }} از {{ $articles->total() }} مقاله</span>{{ $articles->links() }}</div>
    </section>
@endsection
