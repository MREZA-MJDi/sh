@extends('admin.layouts.app')

@section('page-title', 'منابع و جزوات')
@section('page-description', 'بارگذاری و مدیریت جزوه‌ها، فایل‌ها و منابع آموزشی')

@section('content')
    @php($teacherView = auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin'))
    <div class="panel-page-head">
        <div><h2>منابع آموزشی</h2><p>{{ $resources->total() }} فایل و منبع ثبت‌شده</p></div>
        @canany(['resources.manage', 'resources.manage.assigned'])
            <button class="panel-btn" type="button" data-admin-drawer-open="resource-create" data-drawer-title="افزودن منبع">＋ افزودن جزوه</button>
        @endcanany
    </div>

    <section class="panel-grid panel-grid-3">
        <x-admin::stat label="منابع این فهرست" :value="number_format($resources->total())" note="منابع قابل دسترسی" accent="#7b8cff"/>
        <x-admin::stat label="فایل‌های بارگذاری‌شده" :value="number_format($resources->getCollection()->whereNotNull('file_path')->count())" note="در صفحهٔ جاری" accent="#0b8f80"/>
        <x-admin::stat label="منابع منتشرشده" :value="number_format($resources->getCollection()->where('status', 'published')->count())" note="در صفحهٔ جاری" accent="#f0a21a"/>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>فهرست منابع</h3><p>هر فایل به دوره یا کلاس مرتبط است و به‌صورت خصوصی ذخیره می‌شود.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>نوع</th><th>دوره / کلاس</th><th>قیمت</th><th>دسترسی</th><th>وضعیت</th><th>فایل</th>@canany(['resources.manage', 'resources.manage.assigned'])<th>عملیات</th>@endcanany</tr></thead>
                <tbody>
                @forelse ($resources as $resource)
                    <tr>
                        <td><strong>{{ $resource->title }}</strong><div class="panel-muted">{{ $resource->uploader->name ?? '—' }}</div></td>
                        <td>{{ ['pdf'=>'جزوه PDF','book'=>'کتاب','other'=>'سایر'][$resource->kind] ?? $resource->kind }}</td>
                        <td>{{ $resource->course->title ?? $resource->classroom->name ?? '—' }}</td>
                        <td>{{ $resource->is_free ? 'رایگان' : number_format($resource->price).' تومان' }}</td>
                        <td>{{ ['public'=>'عمومی','authenticated'=>'کاربران واردشده','enrolled'=>'اعضای دوره/کلاس'][$resource->visibility] ?? $resource->visibility }}</td>
                        <td><x-admin::badge :variant="$resource->status === 'published' ? 'success' : 'warning'">{{ ['published'=>'منتشرشده','draft'=>'پیش‌نویس','archived'=>'بایگانی'][$resource->status] ?? $resource->status }}</x-admin.badge></td>
                        <td>@if($resource->file_path)<a href="{{ route('admin.resources.download', $resource) }}">دریافت خصوصی</a>@else<span class="panel-muted">فایل ندارد</span>@endif</td>
                        @canany(['resources.manage', 'resources.manage.assigned'])
                            <td class="panel-table-actions">
                                <button class="panel-btn panel-btn-secondary panel-btn-sm" type="button" data-admin-drawer-open="resource-edit-{{ $resource->id }}" data-drawer-title="ویرایش منبع">ویرایش</button>
                                <form method="POST" action="{{ route('admin.resources.destroy', $resource) }}" data-confirm="این منبع حذف شود؟">
                                    @csrf @method('DELETE')
                                    <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف</button>
                                </form>
                            </td>
                        @endcanany
                    </tr>
                    @canany(['resources.manage', 'resources.manage.assigned'])
                        <x-admin::drawer-form :id="'resource-edit-'.$resource->id" title="ویرایش منبع" :action="route('admin.resources.update', $resource)" method="POST" submit-label="ذخیره تغییرات" enctype="multipart/form-data">
                            @method('PATCH')
                            <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $resource->title }}" required maxlength="180"></div>
                            <div class="panel-field"><label>نوع منبع</label><select class="panel-select" name="kind">@foreach (['pdf'=>'جزوه PDF','book'=>'کتاب','other'=>'سایر'] as $key=>$label)<option value="{{ $key }}" @selected($resource->kind === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>دوره مرتبط</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected($resource->course_id === $course->id)>{{ $course->title }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>کلاس مرتبط</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected($resource->classroom_id === $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description" data-rich-text maxlength="20000">{{ $resource->description }}</textarea></div>
                            <label class="panel-checkbox"><input type="checkbox" name="is_free" value="1" @checked($resource->is_free)> منبع رایگان است</label>
                            <div class="panel-field"><label>قیمت (تومان)</label><input class="panel-input" type="number" name="price" min="0" value="{{ $resource->price }}"></div>
                            <div class="panel-field"><label>سطح دسترسی</label><select class="panel-select" name="visibility">@unless($teacherView)<option value="public" @selected($resource->visibility === 'public')>عمومی</option>@endunless<option value="authenticated" @selected($resource->visibility === 'authenticated')>کاربران واردشده</option><option value="enrolled" @selected($resource->visibility === 'enrolled')>اعضای دوره/کلاس</option></select></div>
                            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($resource->status === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>جایگزینی فایل (اختیاری)</label><input class="panel-input" type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"><span class="panel-muted">فایل قبلی پس از ذخیره موفق جایگزین می‌شود.</span></div>
                        </x-admin.drawer-form>
                    @endcanany
                @empty
                    <tr><td colspan="8" class="panel-empty">منبعی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $resources->count() }} از {{ $resources->total() }} منبع</span>{{ $resources->links() }}</div>
    </section>

    @canany(['resources.manage', 'resources.manage.assigned'])
        <x-admin::drawer-form id="resource-create" title="افزودن منبع" :action="route('admin.resources.store')" submit-label="ذخیره منبع" enctype="multipart/form-data">
            <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
            <div class="panel-field"><label>نوع منبع</label><select class="panel-select" name="kind"><option value="pdf">جزوه PDF</option><option value="book">کتاب</option><option value="other">سایر</option></select></div>
            <div class="panel-field"><label>دوره مرتبط</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
            <div class="panel-field"><label>کلاس مرتبط</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description" data-rich-text maxlength="20000"></textarea></div>
            <label class="panel-checkbox"><input type="checkbox" name="is_free" value="1" checked> منبع رایگان است</label>
            <div class="panel-field"><label>قیمت (تومان)</label><input class="panel-input" type="number" name="price" min="0" value="0"></div>
            <div class="panel-field"><label>سطح دسترسی</label><select class="panel-select" name="visibility">@unless($teacherView)<option value="public">عمومی</option>@endunless<option value="authenticated">کاربران واردشده</option><option value="enrolled" selected>اعضای دوره/کلاس</option></select></div>
            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option></select></div>
            <div class="panel-field"><label>فایل</label><input class="panel-input" type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"></div>
        </x-admin.drawer-form>
    @endcanany
@endsection
