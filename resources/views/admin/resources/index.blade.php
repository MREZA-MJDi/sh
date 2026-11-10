@extends('layouts.panel')

@section('page-title', 'منابع و محصولات آموزشی')
@section('page-description', 'مدیریت جزوات، فایل‌های آموزشی و محصولات قابل ارائه')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>افزودن منبع یا محصول</h3><p>فایل‌ها در فضای خصوصی ذخیره می‌شوند و مسیر عمومی مستقیم ندارند.</p></div></div>
        <form method="POST" action="{{ route('admin.resources.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
                <div class="panel-field"><label>نوع</label><select class="panel-select" name="kind"><option value="pdf">جزوه PDF</option><option value="book">کتاب</option><option value="other">سایر</option></select></div>
                <div class="panel-field"><label>دوره مرتبط</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                <div class="panel-field"><label>کلاس مرتبط</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>قیمت (تومان)</label><input class="panel-input" type="number" name="price" min="0" value="0"></div>
                <div class="panel-field"><label>دسترسی</label><select class="panel-select" name="visibility"><option value="enrolled">دانش‌آموزان عضو</option><option value="authenticated">کاربران واردشده</option><option value="public">عمومی</option></select></div>
                <div class="panel-field"><label>فایل</label><input class="panel-input" type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"></div>
                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="published">منتشرشده</option><option value="draft">پیش‌نویس</option><option value="archived">بایگانی</option></select></div>
                <div class="panel-field"><label>توضیحات</label><input class="panel-input" name="description" value="{{ old('description') }}" maxlength="10000"></div>
            </div>
            <div class="flex flex-wrap gap-4 mt-3">
                <label class="panel-checkbox"><input type="checkbox" name="is_free" value="1" checked> منبع رایگان است</label>
            </div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره منبع</button></div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>فهرست منابع</h3><p>مشخصات فایل، نوع دسترسی و وضعیت انتشار</p></div><span class="panel-badge">{{ $resources->total() }} مورد</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>نوع</th><th>ارتباط</th><th>قیمت</th><th>دسترسی</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($resources as $resource)
                    <tr>
                        <td><strong>{{ $resource->title }}</strong><div class="panel-muted">{{ $resource->uploader->name ?? '—' }}</div></td>
                        <td>{{ ['pdf'=>'جزوه','book'=>'کتاب','other'=>'سایر'][$resource->kind] ?? $resource->kind }}</td>
                        <td>{{ $resource->course->title ?? $resource->classroom->name ?? 'عمومی' }}</td>
                        <td>{{ $resource->is_free ? 'رایگان' : number_format($resource->price).' تومان' }}</td>
                        <td>{{ ['public'=>'عمومی','authenticated'=>'کاربران واردشده','enrolled'=>'اعضای کلاس'][$resource->visibility] ?? $resource->visibility }}</td>
                        <td><span class="panel-badge {{ $resource->status==='published' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['published'=>'منتشرشده','draft'=>'پیش‌نویس','archived'=>'بایگانی'][$resource->status] ?? $resource->status }}</span></td>
                        <td class="panel-table-actions">
                            @if ($resource->file_path)<a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.resources.download', $resource) }}">دریافت فایل</a>@endif
                            <form method="POST" action="{{ route('admin.resources.destroy', $resource) }}" data-confirm="این منبع حذف شود؟">
                                @csrf @method('DELETE')
                                <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="panel-empty">منبع آموزشی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $resources->count() }} از {{ $resources->total() }} مورد</span>{{ $resources->links() }}</div>
    </section>
@endsection
