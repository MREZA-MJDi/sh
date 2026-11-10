@extends('admin.layouts.app')

@section('page-title', 'دوره‌ها')
@section('page-description', 'مدیریت دوره‌های رایگان و پولی، ثبت‌نام و نحوه ارائه')

@section('content')
    @php($teacherView = auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin'))
    <div class="panel-page-head">
        <div><h2>دوره‌های آموزشی</h2><p>{{ $courses->total() }} دوره در این فهرست</p></div>
        @can('courses.manage')
            <button class="panel-btn" type="button" data-admin-drawer-open="course-create" data-drawer-title="دوره جدید">＋ ساخت دوره</button>
        @endcan
    </div>

    <section class="panel-grid panel-grid-3">
        <x-admin::stat label="دوره‌های این فهرست" :value="number_format($courses->total())" note="بر اساس دسترسی شما" accent="#7b8cff"/>
        <x-admin::stat label="دسترسی‌های ثبت‌شده" :value="number_format($courses->sum('enrollments_count'))" note="ثبت‌نام‌های دوره‌ها" accent="#0b8f80"/>
        <x-admin::stat label="دوره‌های پولی" :value="number_format($courses->getCollection()->where('type', 'paid')->count())" note="در صفحهٔ جاری" accent="#f0a21a"/>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>فهرست دوره‌ها</h3><p>وضعیت سرویس خارجی مستقل از وضعیت انتشار دوره نمایش داده می‌شود.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>مدرس</th><th>نوع / قیمت</th><th>کلاس‌ها</th><th>ثبت‌نام</th><th>نحوه ارائه</th><th>وضعیت</th>@can('courses.manage')<th>مدیریت</th>@endcan</tr></thead>
                <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td><strong>{{ $course->title }}</strong><div class="panel-muted">{{ $course->subject ?: 'بدون درس' }} · {{ $course->grade ?: 'بدون پایه' }}</div></td>
                        <td>{{ $course->teacher?->name ?? '—' }}</td>
                        <td>{{ $course->type === 'paid' ? 'پولی' : 'رایگان' }}<div class="panel-muted">{{ $course->type === 'paid' ? number_format($course->price).' تومان' : 'رایگان' }}</div></td>
                        <td>{{ number_format($course->classrooms_count) }}</td>
                        <td>{{ number_format($course->enrollments_count) }}</td>
                        <td>{{ ['spotplayer'=>'SpotPlayer','skyroom'=>'اسکای‌روم','onsite'=>'حضوری'][$course->delivery_type] ?? $course->delivery_type }}</td>
                        <td><x-admin::badge :variant="$course->status === 'published' ? 'success' : 'warning'">{{ ['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'][$course->status] ?? $course->status }}</x-admin.badge>
                            @if ($course->delivery_type === 'spotplayer')<div class="panel-muted">{{ $course->integration_status === 'ready' ? 'اتصال تأییدشده' : 'در انتظار تأیید سرویس' }}</div>@endif
                        </td>
                        @can('courses.manage')
                            <td><button class="panel-btn panel-btn-secondary panel-btn-sm" type="button" data-admin-drawer-open="course-edit-{{ $course->id }}" data-drawer-title="ویرایش دوره">ویرایش</button></td>
                        @endcan
                    </tr>
                    @can('courses.manage')
                        <x-admin::drawer-form :id="'course-edit-'.$course->id" title="ویرایش دوره" :action="route('admin.courses.update', $course)" submit-label="ذخیره دوره">
                            @method('PATCH')
                            <div class="panel-field"><label>عنوان دوره</label><input class="panel-input" name="title" value="{{ $course->title }}" required maxlength="180"></div>
                            <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description" maxlength="10000">{{ $course->description }}</textarea></div>
                            <div class="panel-field"><label>درس</label><input class="panel-input" name="subject" value="{{ $course->subject }}"></div>
                            <div class="panel-field"><label>پایه</label><input class="panel-input" name="grade" value="{{ $course->grade }}"></div>
                            <div class="panel-field"><label>مدرس</label><select class="panel-select" name="teacher_id"><option value="">بدون مدرس</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected($course->teacher_id === $teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>نوع دوره</label><select class="panel-select" name="type"><option value="free" @selected($course->type === 'free')>رایگان</option><option value="paid" @selected($course->type === 'paid')>پولی</option></select></div>
                            <div class="panel-field"><label>قیمت (تومان)</label><input class="panel-input" type="number" name="price" min="0" value="{{ $course->price }}"></div>
                            <div class="panel-field"><label>نحوه ارائه</label><select class="panel-select" name="delivery_type" data-reset-spotplayer-id>@foreach (['spotplayer'=>'ویدیو (SpotPlayer)','skyroom'=>'کلاس زنده (اسکای‌روم)','onsite'=>'حضوری'] as $key=>$label)<option value="{{ $key }}" @selected($course->delivery_type === $key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="panel-field"><label>شناسه دوره در SpotPlayer</label><input class="panel-input" name="spotplayer_course_id" value="{{ $course->spotplayer_course_id }}" dir="ltr"></div>
                            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','published'=>'منتشرشده','archived'=>'بایگانی'] as $key=>$label)<option value="{{ $key }}" @selected($course->status === $key)>{{ $label }}</option>@endforeach</select></div>
                        </x-admin.drawer-form>
                    @endcan
                @empty
                    <tr><td colspan="8" class="panel-empty">دوره‌ای برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $courses->count() }} از {{ $courses->total() }} دوره</span>{{ $courses->links() }}</div>
    </section>

    @can('courses.manage')
        <x-admin::drawer-form id="course-create" title="دوره جدید" :action="route('admin.courses.store')" submit-label="ایجاد دوره">
            <div class="panel-field"><label>عنوان دوره</label><input class="panel-input" name="title" required maxlength="180"></div>
            <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description"></textarea></div>
            <div class="panel-field"><label>درس</label><input class="panel-input" name="subject" maxlength="100"></div>
            <div class="panel-field"><label>پایه</label><input class="panel-input" name="grade" maxlength="50"></div>
            <div class="panel-field"><label>مدرس</label><select class="panel-select" name="teacher_id"><option value="">بدون مدرس</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}">{{ $teacher->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>نوع دوره</label><select class="panel-select" name="type"><option value="free">رایگان</option><option value="paid">پولی</option></select></div>
            <div class="panel-field"><label>قیمت (تومان)</label><input class="panel-input" type="number" name="price" min="0" value="0"></div>
            <div class="panel-field"><label>نحوه ارائه</label><select class="panel-select" name="delivery_type" data-reset-spotplayer-id><option value="spotplayer">ویدیو (SpotPlayer)</option><option value="skyroom">کلاس زنده (اسکای‌روم)</option><option value="onsite">حضوری</option></select></div>
            <div class="panel-field"><label>شناسه دوره در SpotPlayer</label><input class="panel-input" name="spotplayer_course_id" dir="ltr" maxlength="190"></div>
            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="published">منتشرشده</option><option value="archived">بایگانی</option></select></div>
        </x-admin.drawer-form>
    @endcan
@endsection
