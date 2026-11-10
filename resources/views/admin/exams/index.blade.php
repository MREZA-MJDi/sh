@extends('layouts.panel')

@section('page-title', 'آزمون‌ها و نتایج')
@section('page-description', 'تعریف آزمون داخلی یا ثبت شناسه آزمون سرویس خارجی')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>ثبت آزمون</h3><p>برای آزمون Quiz24 شناسه‌ای را وارد کنید که از پنل رسمی سرویس دریافت شده باشد.</p></div></div>
        <form method="POST" action="{{ route('admin.exams.store') }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان آزمون</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
                <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس مشخص</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                <div class="panel-field"><label>ارائه‌دهنده آزمون</label><select class="panel-select" name="provider"><option value="internal">داخلی</option><option value="quiz24">Quiz24</option></select></div>
                <div class="panel-field"><label>شناسه آزمون خارجی</label><input class="panel-input" name="external_id" value="{{ old('external_id') }}" maxlength="190"></div>
                <div class="panel-field"><label>زمان شروع</label><input class="panel-input" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></div>
                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="scheduled">زمان‌بندی‌شده</option><option value="published">منتشرشده</option><option value="closed">بسته‌شده</option></select></div>
                <div class="panel-field"><label>توضیحات</label><input class="panel-input" name="description" value="{{ old('description') }}" maxlength="10000"></div>
            </div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت آزمون</button></div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>فهرست آزمون‌ها</h3><p>نتایج فقط پس از ثبت داخلی یا پیاده‌سازی همگام‌سازی واقعی نمایش داده می‌شوند.</p></div><span class="panel-badge">{{ $exams->total() }} آزمون</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>آزمون</th><th>کلاس / دوره</th><th>منبع</th><th>تاریخ</th><th>نتایج</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($exams as $exam)
                    <tr>
                        <td><strong>{{ $exam->title }}</strong><div class="panel-muted">{{ $exam->external_id ?: 'شناسه داخلی' }}</div></td>
                        <td>{{ $exam->classroom->name ?? $exam->course->title ?? '—' }}</td>
                        <td>{{ $exam->provider==='quiz24' ? 'Quiz24' : 'داخلی' }}</td>
                        <td>{{ $exam->starts_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $exam->results_count }}</td>
                        <td><span class="panel-badge {{ $exam->status==='published' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['draft'=>'پیش‌نویس','scheduled'=>'زمان‌بندی‌شده','published'=>'منتشرشده','closed'=>'بسته‌شده'][$exam->status] ?? $exam->status }}</span></td>
                        <td class="panel-table-actions">
                            <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.exams.results', $exam) }}">مشاهده نتایج</a>
                            <details class="panel-details">
                                <summary>ویرایش</summary>
                                <div class="panel-details-content">
                                    <form method="POST" action="{{ route('admin.exams.update', $exam) }}" class="grid gap-2">
                                        @csrf @method('PATCH')
                                        <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" value="{{ $exam->title }}" required maxlength="180"></div>
                                        <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected($exam->classroom_id===$classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                                        <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected($exam->course_id===$course->id)>{{ $course->title }}</option>@endforeach</select></div>
                                        <div class="panel-field"><label>ارائه‌دهنده</label><select class="panel-select" name="provider"><option value="internal" @selected($exam->provider==='internal')>داخلی</option><option value="quiz24" @selected($exam->provider==='quiz24')>Quiz24</option></select></div>
                                        <div class="panel-field"><label>شناسه آزمون خارجی</label><input class="panel-input" name="external_id" value="{{ $exam->external_id }}" maxlength="190" dir="ltr"></div>
                                        <div class="panel-field"><label>زمان شروع</label><input class="panel-input" type="datetime-local" name="starts_at" value="{{ $exam->starts_at?->format('Y-m-d\\TH:i') }}"></div>
                                        <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','scheduled'=>'زمان‌بندی‌شده','published'=>'منتشرشده','closed'=>'بسته‌شده'] as $key=>$label)<option value="{{ $key }}" @selected($exam->status===$key)>{{ $label }}</option>@endforeach</select></div>
                                        <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description" maxlength="10000">{{ $exam->description }}</textarea></div>
                                        <button class="panel-btn panel-btn-sm" type="submit">ذخیره تغییرات</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="panel-empty">آزمونی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $exams->count() }} از {{ $exams->total() }} آزمون</span>{{ $exams->links() }}</div>
    </section>
@endsection
