@extends('admin.layouts.app')

@section('page-title', 'مدیریت بنرها')
@section('page-description', 'بارگذاری تصویر، تعیین محل نمایش، زمان‌بندی و ترتیب بنرها')

@section('content')
    <div class="panel-page-head"><div><h2>بنرهای سایت</h2><p>{{ number_format($banners->total()) }} بنر ثبت‌شده</p></div><a class="panel-btn panel-btn-secondary" href="{{ route('admin.seo.index') }}">بازگشت به سئو</a></div>
    <form class="panel-card" method="POST" action="{{ route('admin.seo.banners.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel-card-title"><div><h3>ثبت بنر جدید</h3><p>تصویر به‌صورت امن در فضای عمومی ذخیره می‌شود.</p></div></div>
        <div class="panel-form-grid panel-form-grid-3">
            <div class="panel-field"><label>عنوان داخلی</label><input class="panel-input" name="title" required maxlength="180" value="{{ old('title') }}"></div>
            <div class="panel-field"><label>متن جایگزین تصویر (Alt)</label><input class="panel-input" name="alt_text" required maxlength="180" value="{{ old('alt_text') }}"></div>
            <div class="panel-field"><label>فایل تصویر</label><input class="panel-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>
            <div class="panel-field"><label>محل نمایش</label><select class="panel-select" name="placement"><option value="articles_top">بالای فهرست مقاله‌ها</option><option value="articles_sidebar">کنار مقاله</option><option value="sitewide">عمومی در صفحه مقاله‌ها</option></select></div>
            <div class="panel-field"><label>لینک مقصد</label><input class="panel-input" name="target_url" maxlength="2048" placeholder="/articles یا https://..."></div>
            <div class="panel-field"><label>ترتیب نمایش</label><input class="panel-input" type="number" min="0" max="100000" name="sort_order" value="0" required></div>
            <div class="panel-field"><label>شروع نمایش</label><input class="panel-input" type="datetime-local" name="starts_at"></div>
            <div class="panel-field"><label>پایان نمایش</label><input class="panel-input" type="datetime-local" name="ends_at"></div>
            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="active">فعال</option><option value="inactive">غیرفعال</option></select></div>
        </div>
        <label class="panel-checkbox"><input type="checkbox" name="open_in_new_tab" value="1"> بازکردن لینک در زبانه جدید</label>
        <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت بنر</button></div>
    </form>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>فهرست بنرها</h3><p>بنر فعال فقط در بازه زمانی تعیین‌شده به بازدیدکننده نمایش داده می‌شود.</p></div></div>
        <div class="panel-table-wrap"><table class="panel-table"><thead><tr><th>تصویر</th><th>عنوان و محل</th><th>ترتیب</th><th>بازه نمایش</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
        @forelse($banners as $banner)
            <tr>
                <td><img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" loading="lazy" style="width:100px;height:58px;object-fit:cover;border-radius:8px"></td>
                <td><strong>{{ $banner->title }}</strong><div class="panel-muted">{{ ['articles_top'=>'بالای مقاله‌ها','articles_sidebar'=>'کنار مقاله','sitewide'=>'عمومی'][$banner->placement] ?? $banner->placement }}</div></td>
                <td>{{ $banner->sort_order }}</td>
                <td><span class="panel-muted">{{ $banner->starts_at?->format('Y/m/d H:i') ?? 'بدون شروع' }}</span><br><span class="panel-muted">{{ $banner->ends_at?->format('Y/m/d H:i') ?? 'بدون پایان' }}</span></td>
                <td><span class="panel-badge {{ $banner->status === 'active' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['draft'=>'پیش‌نویس','active'=>'فعال','inactive'=>'غیرفعال'][$banner->status] ?? $banner->status }}</span></td>
                <td><details><summary class="panel-btn panel-btn-secondary panel-btn-sm">ویرایش</summary>
                    <form method="POST" action="{{ route('admin.seo.banners.update', $banner) }}" enctype="multipart/form-data" class="panel-card">
                        @csrf @method('PATCH')
                        <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $banner->title }}" required maxlength="180"></div>
                        <div class="panel-field"><label>متن جایگزین</label><input class="panel-input" name="alt_text" value="{{ $banner->alt_text }}" required maxlength="180"></div>
                        <div class="panel-field"><label>تصویر جدید (اختیاری)</label><input class="panel-input" type="file" name="image" accept="image/jpeg,image/png,image/webp"></div>
                        <div class="panel-field"><label>محل نمایش</label><select class="panel-select" name="placement">@foreach(['articles_top'=>'بالای مقاله‌ها','articles_sidebar'=>'کنار مقاله','sitewide'=>'عمومی'] as $key=>$label)<option value="{{ $key }}" @selected($banner->placement===$key)>{{ $label }}</option>@endforeach</select></div>
                        <div class="panel-field"><label>لینک مقصد</label><input class="panel-input" name="target_url" value="{{ $banner->target_url }}"></div>
                        <div class="panel-field"><label>ترتیب</label><input class="panel-input" type="number" name="sort_order" min="0" value="{{ $banner->sort_order }}" required></div>
                        <div class="panel-field"><label>شروع</label><input class="panel-input" type="datetime-local" name="starts_at" value="{{ $banner->starts_at?->format('Y-m-d\TH:i') }}"></div>
                        <div class="panel-field"><label>پایان</label><input class="panel-input" type="datetime-local" name="ends_at" value="{{ $banner->ends_at?->format('Y-m-d\TH:i') }}"></div>
                        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach(['draft'=>'پیش‌نویس','active'=>'فعال','inactive'=>'غیرفعال'] as $key=>$label)<option value="{{ $key }}" @selected($banner->status===$key)>{{ $label }}</option>@endforeach</select></div>
                        <label class="panel-checkbox"><input type="checkbox" name="open_in_new_tab" value="1" @checked($banner->open_in_new_tab)> زبانه جدید</label>
                        <button class="panel-btn" type="submit">ذخیره تغییرات</button>
                    </form>
                    <form method="POST" action="{{ route('admin.seo.banners.destroy', $banner) }}" data-confirm="این بنر و فایل تصویر آن حذف شود؟">@csrf @method('DELETE')<button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف بنر</button></form>
                </details></td>
            </tr>
        @empty<tr><td colspan="6" class="panel-empty">هنوز بنری ثبت نشده است.</td></tr>@endforelse
        </tbody></table></div>
        <div class="panel-pagination"><span>{{ $banners->count() }} از {{ $banners->total() }} بنر</span>{{ $banners->links() }}</div>
    </section>
@endsection
