@extends('admin.layouts.app')

@section('page-title', 'آزمون‌ها')
@section('page-description', 'ساخت آزمون و مشاهده نتایج در همان فضای کاری آکادمی')

@section('content')
    @php($teacherView = auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin'))
    <div class="panel-page-head">
        <div><h2>آزمون‌ها و نتایج</h2><p>{{ number_format($exams->total()) }} آزمون در فهرست شما</p></div>
        @canany(['exams.manage', 'exams.manage.assigned'])
            <button class="panel-btn" type="button" data-admin-drawer-open="exam-create" data-drawer-title="آزمون جدید">＋ آزمون جدید</button>
        @endcanany
    </div>

    <section class="panel-card">
        <div class="panel-card-title">
            <div><h3>فهرست آزمون‌ها</h3><p>آزمون‌های کلاس‌ها و دوره‌های در دسترس شما</p></div>
        </div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان آزمون</th><th>کلاس / دوره</th><th>منبع</th><th>زمان شروع</th><th>نتایج</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($exams as $exam)
                    <tr>
                        <td><strong>{{ $exam->title }}</strong><div class="panel-muted">#{{ $exam->id }}</div></td>
                        <td>{{ $exam->classroom->name ?? $exam->course->title ?? '—' }}</td>
                        <td>{{ $exam->provider === 'quiz24' ? 'Quiz24' : 'داخلی' }}</td>
                        <td>{{ $exam->starts_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ number_format($exam->results_count) }}</td>
                        <td><x-admin::badge :variant="$exam->status === 'published' ? 'success' : 'warning'">{{ ['draft'=>'پیش‌نویس','scheduled'=>'زمان‌بندی‌شده','published'=>'منتشرشده','closed'=>'بسته‌شده'][$exam->status] ?? $exam->status }}</x-admin.badge></td>
                        <td class="panel-table-actions">
                            <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.exams.results', $exam) }}">نتایج</a>
                            @if ($exam->provider === 'internal')
                                <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.exams.questions', $exam) }}">سؤال‌ها</a>
                                <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.exams.attempts', $exam) }}">برگه‌ها</a>
                            @endif
                            @if (! $teacherView || $exam->provider === 'internal')
                                @canany(['exams.manage', 'exams.manage.assigned'])
                                    <button class="panel-btn panel-btn-secondary panel-btn-sm" type="button" data-admin-drawer-open="exam-edit-{{ $exam->id }}" data-drawer-title="ویرایش آزمون">ویرایش</button>
                                @endcanany
                            @endif
                        </td>
                    </tr>

                    @if (! $teacherView || $exam->provider === 'internal')
                        @canany(['exams.manage', 'exams.manage.assigned'])
                            <x-admin::drawer-form :id="'exam-edit-'.$exam->id" title="ویرایش آزمون" :action="route('admin.exams.update', $exam)" submit-label="ذخیره آزمون">
                                @method('PATCH')
                                <div class="panel-field"><label>عنوان آزمون</label><input class="panel-input" name="title" value="{{ $exam->title }}" required maxlength="180"></div>
                                <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس مشخص</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}" @selected($exam->classroom_id === $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></div>
                                <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id"><option value="">بدون دوره مشخص</option>@foreach ($courses as $course)<option value="{{ $course->id }}" @selected($exam->course_id === $course->id)>{{ $course->title }}</option>@endforeach</select></div>
                                @if ($teacherView)
                                    <input type="hidden" name="provider" value="internal">
                                @else
                                    <div class="panel-field"><label>ارائه‌دهنده</label><select class="panel-select" name="provider"><option value="internal" @selected($exam->provider === 'internal')>داخلی</option><option value="quiz24" @selected($exam->provider === 'quiz24')>Quiz24</option></select></div>
                                    <div class="panel-field"><label>شناسه آزمون Quiz24</label><input class="panel-input" name="external_id" value="{{ $exam->external_id }}" maxlength="190" dir="ltr"></div>
                                @endif
                                <div class="panel-field"><label>مدت آزمون (ساعت)</label><select class="panel-select" name="duration_hours">@foreach ([1,2,3,4] as $hours)<option value="{{ $hours }}" @selected((int) ceil(($exam->duration_minutes ?? 120) / 60) === $hours)>{{ $hours }} ساعت</option>@endforeach</select></div>
                                <div class="panel-field"><label class="panel-checkbox"><input type="hidden" name="show_to_guardians" value="0"><input type="checkbox" name="show_to_guardians" value="1" @checked($exam->show_to_guardians ?? true)> نمایش نتیجه به اولیا</label></div>
                                <div class="panel-field"><label>زمان شروع</label><input class="panel-input" type="datetime-local" name="starts_at" value="{{ $exam->starts_at?->format('Y-m-d\TH:i') }}"></div>
                                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['draft'=>'پیش‌نویس','scheduled'=>'زمان‌بندی‌شده','published'=>'منتشرشده','closed'=>'بسته‌شده'] as $key=>$label)<option value="{{ $key }}" @selected($exam->status === $key)>{{ $label }}</option>@endforeach</select></div>
                                <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description" maxlength="10000">{{ $exam->description }}</textarea></div>
                            </x-admin.drawer-form>
                        @endcanany
                    @endif
                @empty
                    <tr><td colspan="7" class="panel-empty">آزمونی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $exams->count() }} از {{ $exams->total() }} آزمون</span>{{ $exams->links() }}</div>
    </section>

    @canany(['exams.manage', 'exams.manage.assigned'])
        <x-admin::drawer-form id="exam-create" title="آزمون جدید" :action="route('admin.exams.store')" submit-label="ثبت آزمون">
            <div class="panel-field"><label>عنوان آزمون</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
            <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس مشخص</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id"><option value="">بدون دوره مشخص</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
            @if ($teacherView)
                <input type="hidden" name="provider" value="internal">
            @else
                <div class="panel-field"><label>ارائه‌دهنده آزمون</label><select class="panel-select" name="provider"><option value="internal">داخلی</option><option value="quiz24">Quiz24</option></select></div>
                <div class="panel-field"><label>شناسه آزمون Quiz24 (در صورت وجود)</label><input class="panel-input" name="external_id" value="{{ old('external_id') }}" maxlength="190" dir="ltr"></div>
            @endif
            <div class="panel-field"><label>مدت آزمون (ساعت)</label><select class="panel-select" name="duration_hours">@foreach ([1,2,3,4] as $hours)<option value="{{ $hours }}" @selected((int) old('duration_hours', 2) === $hours)>{{ $hours }} ساعت</option>@endforeach</select></div>
            <div class="panel-field"><label>تعداد دفعات مجاز</label><select class="panel-select" name="attempts_allowed">@foreach ([1,2,3,4,5] as $count)<option value="{{ $count }}" @selected((int) old('attempts_allowed', 1) === $count)>{{ $count }} بار</option>@endforeach</select></div>
            <div class="panel-field"><label class="panel-checkbox"><input type="hidden" name="show_to_guardians" value="0"><input type="checkbox" name="show_to_guardians" value="1" checked> نمایش نتیجه به اولیا</label></div>
            <div class="panel-field"><label>زمان شروع</label><input class="panel-input" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></div>
            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="scheduled">زمان‌بندی‌شده</option><option value="published">منتشرشده</option><option value="closed">بسته‌شده</option></select></div>
            <div class="panel-field"><label>توضیحات</label><textarea class="panel-textarea" name="description" maxlength="10000">{{ old('description') }}</textarea></div>
            @if ($teacherView)
                <div class="panel-alert">آزمون به دوره یا کلاس تخصیص‌یافته متصل می‌شود؛ اتصال به Quiz24 از دسترسی مدیریتی انجام می‌شود.</div>
            @endif
        </x-admin.drawer-form>
    @endcanany
@endsection
