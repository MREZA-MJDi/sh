@extends('layouts.panel')

@section('page-title', 'دوره‌ها')
@section('page-description', 'تعریف دوره‌های آموزشی، مدرس، قیمت و شیوه ارائه')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>ایجاد دوره</h3><p>دوره پس از انتشار در بخش‌های مجاز سامانه قابل استفاده است.</p></div></div>
        <form method="POST" action="{{ route('admin.courses.store') }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان دوره</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
                <div class="panel-field"><label>درس</label><input class="panel-input" name="subject" value="{{ old('subject') }}" maxlength="100"></div>
                <div class="panel-field"><label>پایه</label><input class="panel-input" name="grade" value="{{ old('grade') }}" maxlength="50" placeholder="مثلاً دوازدهم"></div>
                <div class="panel-field"><label>مدرس</label><select class="panel-select" name="teacher_id"><option value="">بدون مدرس</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>نوع دوره</label><select class="panel-select" name="type"><option value="free">رایگان</option><option value="paid">پولی</option></select></div>
                <div class="panel-field"><label>قیمت (تومان)</label><input class="panel-input" name="price" type="number" min="0" value="0"></div>
                <div class="panel-field"><label>نحوه ارائه</label><select class="panel-select" name="delivery_type"><option value="spotplayer">ویدیوی اسپات پلیر</option><option value="skyroom">کلاس زنده اسکای‌روم</option><option value="onsite">حضوری</option></select></div>
                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option><option value="archived">بایگانی</option></select></div>
                <div class="panel-field"><label>توضیحات</label><input class="panel-input" name="description" value="{{ old('description') }}" maxlength="10000"></div>
            </div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت دوره</button></div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>فهرست دوره‌ها</h3><p>ویرایش هر دوره از بخش جزئیات همان ردیف انجام می‌شود.</p></div><span class="panel-badge">{{ $courses->total() }} دوره</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>مدرس</th><th>نوع</th><th>قیمت</th><th>کلاس‌ها</th><th>وضعیت</th><th>ویرایش</th></tr></thead>
                <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td><strong>{{ $course->title }}</strong><div class="panel-muted">{{ $course->subject ?: 'بدون درس مشخص' }} · {{ $course->grade ?: 'بدون پایه' }}</div></td>
                        <td>{{ $course->teacher->name ?? '—' }}</td>
                        <td>{{ $course->type === 'paid' ? 'پولی' : 'رایگان' }}</td>
                        <td>{{ $course->price ? number_format($course->price).' تومان' : '—' }}</td>
                        <td>{{ $course->classrooms_count }}</td>
                        <td><span class="panel-badge {{ $course->status === 'published' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['published'=>'منتشرشده','draft'=>'پیش‌نویس','archived'=>'بایگانی'][$course->status] ?? $course->status }}</span></td>
                        <td>
                            <details class="panel-details">
                                <summary>ویرایش</summary>
                                <div class="panel-details-content">
                                    <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="grid gap-2">
                                        @csrf @method('PATCH')
                                        <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $course->title }}" required></div>
                                        <div class="panel-field"><label>مدرس</label><select class="panel-select" name="teacher_id"><option value="">بدون مدرس</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($course->teacher_id === $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
                                        <div class="panel-form-grid">
                                            <div class="panel-field"><label>نوع</label><select class="panel-select" name="type"><option value="free" @selected($course->type==='free')>رایگان</option><option value="paid" @selected($course->type==='paid')>پولی</option></select></div>
                                            <div class="panel-field"><label>قیمت</label><input class="panel-input" type="number" min="0" name="price" value="{{ $course->price }}"></div>
                                        </div>
                                        <div class="panel-form-grid">
                                            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$value)<option value="{{ $key }}" @selected($course->status===$key)>{{ $value }}</option>@endforeach</select></div>
                                            <div class="panel-field"><label>ارائه</label><select class="panel-select" name="delivery_type">@foreach (['spotplayer'=>'اسپات پلیر','skyroom'=>'اسکای‌روم','onsite'=>'حضوری'] as $key=>$value)<option value="{{ $key }}" @selected($course->delivery_type===$key)>{{ $value }}</option>@endforeach</select></div>
                                        </div>
                                        <input type="hidden" name="description" value="{{ $course->description }}">
                                        <input type="hidden" name="subject" value="{{ $course->subject }}">
                                        <input type="hidden" name="grade" value="{{ $course->grade }}">
                                        <button class="panel-btn panel-btn-sm" type="submit">ذخیره تغییرات</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" data-confirm="حذف این دوره؟" class="mt-2">
                                        @csrf @method('DELETE')
                                        <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">حذف دوره</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="panel-empty">هنوز دوره‌ای ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $courses->count() }} از {{ $courses->total() }} دوره</span>{{ $courses->links() }}</div>
    </section>
@endsection
