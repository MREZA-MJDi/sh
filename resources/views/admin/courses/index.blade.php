@extends('layouts.panel')

@section('page-title', 'دوره‌ها')
@section('page-description', 'ایجاد دوره محلی، تعیین دسترسی و اتصال کنترل‌شده به سرویس ویدیویی')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title">
            <div>
                <h3>ایجاد دوره</h3>
                <p>دوره ابتدا در سامانه شیخان ساخته می‌شود. ساخت خودکار دوره در SpotPlayer تا دریافت وب‌سرویس انجام نمی‌شود.</p>
            </div>
            <span class="panel-badge">ایجاد محلی</span>
        </div>

        <form method="POST" action="{{ route('admin.courses.store') }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان دوره</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
                <div class="panel-field"><label>درس</label><input class="panel-input" name="subject" value="{{ old('subject') }}" maxlength="100"></div>
                <div class="panel-field"><label>پایه تحصیلی</label><input class="panel-input" name="grade" value="{{ old('grade') }}" maxlength="50" placeholder="مثلاً دوازدهم"></div>
                <div class="panel-field">
                    <label>مدرس مسئول</label>
                    <select class="panel-select" name="teacher_id">
                        <option value="">بدون مدرس</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="panel-field"><label>نوع دوره</label><select class="panel-select" name="type"><option value="free">رایگان</option><option value="paid">پولی</option></select></div>
                <div class="panel-field"><label>قیمت (تومان، برای دوره پولی)</label><input class="panel-input" name="price" type="number" min="1" max="1000000000" value="{{ old('price') }}"></div>
                <div class="panel-field">
                    <label>نحوه ارائه</label>
                    <select class="panel-select" name="delivery_type" onchange="if(this.value !== 'spotplayer') this.form.querySelector('[name=spotplayer_course_id]').value = ''">
                        <option value="spotplayer">ویدیو با SpotPlayer</option>
                        <option value="skyroom">کلاس زنده با اسکای‌روم</option>
                        <option value="onsite">حضوری</option>
                    </select>
                </div>
                <div class="panel-field"><label>شناسه دوره در SpotPlayer (در صورت وجود)</label><input class="panel-input" name="spotplayer_course_id" value="{{ old('spotplayer_course_id') }}" maxlength="190" dir="ltr" placeholder="External Course ID"></div>
                <div class="panel-field"><label>وضعیت انتشار در شیخان</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option><option value="archived">بایگانی</option></select></div>
            </div>
            <div class="panel-field mt-3"><label>توضیحات</label><textarea class="panel-textarea" name="description" maxlength="10000">{{ old('description') }}</textarea></div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ایجاد دوره در شیخان</button></div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title">
            <div><h3>فهرست دوره‌ها</h3><p>تعداد ثبت‌نام‌ها از جدول دسترسی دوره محاسبه می‌شود؛ ثبت شناسه خارجی به معنی تأیید اتصال نیست.</p></div>
            <span class="panel-badge">{{ $courses->total() }} دوره</span>
        </div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان دوره</th><th>مدرس</th><th>نوع / قیمت</th><th>کلاس</th><th>دسترسی فعال</th><th>اتصال سرویس</th><th>مدیریت</th></tr></thead>
                <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td><strong>{{ $course->title }}</strong><div class="panel-muted">{{ $course->subject ?: 'بدون درس' }} · {{ $course->grade ?: 'بدون پایه' }}</div></td>
                        <td>{{ $course->teacher->name ?? '—' }}</td>
                        <td>{{ $course->type === 'paid' ? 'پولی' : 'رایگان' }}<div class="panel-muted">{{ $course->type === 'paid' ? number_format($course->price).' تومان' : 'بدون هزینه' }}</div></td>
                        <td>{{ $course->classrooms_count }}</td>
                        <td>{{ $course->enrollments_count }}</td>
                        <td>
                            @if ($course->delivery_type !== 'spotplayer')
                                <span class="panel-badge">بدون اتصال ویدیو</span>
                            @elseif ($course->integration_status === 'ready')
                                <span class="panel-badge panel-badge-success">تأییدشده</span>
                            @elseif ($course->spotplayer_course_id)
                                <span class="panel-badge panel-badge-warning">منتظر تأیید API</span>
                            @else
                                <span class="panel-badge panel-badge-warning">شناسه ثبت نشده</span>
                            @endif
                            @if ($course->spotplayer_course_id)
                                <div class="panel-muted" dir="ltr">{{ $course->spotplayer_course_id }}</div>
                            @endif
                        </td>
                        <td>
                            <details class="panel-details">
                                <summary>ویرایش دوره</summary>
                                <div class="panel-details-content">
                                    <form method="POST" action="{{ route('admin.courses.update', $course) }}" class="grid gap-2">
                                        @csrf @method('PATCH')
                                        <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $course->title }}" required></div>
                                        <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description">{{ $course->description }}</textarea></div>
                                        <div class="panel-form-grid">
                                            <div class="panel-field"><label>درس</label><input class="panel-input" name="subject" value="{{ $course->subject }}"></div>
                                            <div class="panel-field"><label>پایه</label><input class="panel-input" name="grade" value="{{ $course->grade }}"></div>
                                        </div>
                                        <div class="panel-field"><label>مدرس</label><select class="panel-select" name="teacher_id"><option value="">بدون مدرس</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($course->teacher_id === $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
                                        <div class="panel-form-grid">
                                            <div class="panel-field"><label>نوع</label><select class="panel-select" name="type"><option value="free" @selected($course->type==='free')>رایگان</option><option value="paid" @selected($course->type==='paid')>پولی</option></select></div>
                                            <div class="panel-field"><label>قیمت (تومان)</label><input class="panel-input" type="number" min="1" name="price" value="{{ $course->price }}"></div>
                                        </div>
                                        <div class="panel-field"><label>نحوه ارائه</label><select class="panel-select" name="delivery_type" onchange="if(this.value !== 'spotplayer') this.form.querySelector('[name=spotplayer_course_id]').value = ''">@foreach (['spotplayer'=>'ویدیوی SpotPlayer','skyroom'=>'کلاس زنده اسکای‌روم','onsite'=>'حضوری'] as $key=>$label)<option value="{{ $key }}" @selected($course->delivery_type===$key)>{{ $label }}</option>@endforeach</select></div>
                                        <div class="panel-field"><label>شناسه دوره در SpotPlayer</label><input class="panel-input" name="spotplayer_course_id" value="{{ $course->spotplayer_course_id }}" dir="ltr" maxlength="190"></div>
                                        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($course->status===$key)>{{ $label }}</option>@endforeach</select></div>
                                        <button class="panel-btn panel-btn-sm" type="submit">ذخیره تغییرات</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $course) }}" data-confirm="در صورت وجود کلاس، ثبت‌نام یا لایسنس حذف ممکن نیست. حذف شود؟" class="mt-2">
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
