@extends('admin.layouts.app')
@section('page-title', 'مدیریت نظرهای صفحه اصلی')
@section('page-description', 'فقط نظرهای منتشرشده در صفحه اصلی نمایش داده می‌شوند.')
@section('content')
<div class="panel-page-head" data-tone="learning">
    <div><h2>نظرهای دانش‌آموزان</h2><p>نام نمایشی، متن، امتیاز و وضعیت انتشار را مدیریت کنید.</p></div>
    <a class="panel-btn panel-btn-secondary" href="{{ route('admin.seo.index') }}">بازگشت به سئو</a>
</div>
<section class="panel-card">
    <div class="panel-card-title"><div><h3>ثبت نظر جدید</h3><p>نظر فقط پس از انتشار در صفحه اصلی نمایش داده می‌شود.</p></div></div>
    <form method="POST" action="{{ route('admin.seo.testimonials.store') }}" class="panel-form-grid">
        @csrf
        <div class="panel-field"><label>نام نمایشی</label><input class="panel-input" name="name" required maxlength="160"></div>
        <div class="panel-field"><label>سمت / پایه (اختیاری)</label><input class="panel-input" name="role" maxlength="160"></div>
        <div class="panel-field"><label>امتیاز</label><select class="panel-select" name="rating">@for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }}</option>@endfor</select></div>
        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option><option value="archived">بایگانی</option></select></div>
        <div class="panel-field"><label>ترتیب نمایش</label><input class="panel-input" type="number" name="sort_order" min="0" value="0"></div>
        <div class="panel-field" style="grid-column:1/-1"><label>متن نظر</label><textarea class="panel-textarea" name="quote" rows="3" required maxlength="3000"></textarea></div>
        <div><button class="panel-btn" type="submit">ثبت نظر</button></div>
    </form>
</section>
<section class="panel-card">
    <div class="panel-card-title"><div><h3>نظرهای ثبت‌شده</h3><p>{{ number_format($testimonials->total()) }} مورد</p></div></div>
    @forelse($testimonials as $testimonial)
        <div style="padding:16px 0;border-bottom:1px solid var(--panel-border,#e5e7eb)">
            <form method="POST" action="{{ route('admin.seo.testimonials.update', $testimonial) }}" class="panel-form-grid">
                @csrf @method('PATCH')
                <div class="panel-field"><label>نام نمایشی</label><input class="panel-input" name="name" value="{{ $testimonial->name }}" required maxlength="160"></div>
                <div class="panel-field"><label>سمت / پایه</label><input class="panel-input" name="role" value="{{ $testimonial->role }}" maxlength="160"></div>
                <div class="panel-field"><label>امتیاز</label><select class="panel-select" name="rating">@for($i=5;$i>=1;$i--)<option value="{{ $i }}" @selected($testimonial->rating === $i)>{{ $i }}</option>@endfor</select></div>
                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft" @selected($testimonial->status==='draft')>پیش‌نویس</option><option value="published" @selected($testimonial->status==='published')>منتشرشده</option><option value="archived" @selected($testimonial->status==='archived')>بایگانی</option></select></div>
                <div class="panel-field"><label>ترتیب نمایش</label><input class="panel-input" type="number" name="sort_order" min="0" value="{{ $testimonial->sort_order }}"></div>
                <div class="panel-field" style="grid-column:1/-1"><label>متن نظر</label><textarea class="panel-textarea" name="quote" rows="3" required maxlength="3000">{{ $testimonial->quote }}</textarea></div>
                <div><button class="panel-btn" type="submit">ذخیره تغییرات</button></div>
            </form>
            <form method="POST" action="{{ route('admin.seo.testimonials.destroy', $testimonial) }}" style="margin-top:8px" onsubmit="return confirm('این نظر حذف شود؟')">@csrf @method('DELETE')<button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف</button></form>
        </div>
    @empty
        <p class="panel-empty">هنوز نظری ثبت نشده است.</p>
    @endforelse
    <div class="panel-pagination">{{ $testimonials->links() }}</div>
</section>
@endsection
