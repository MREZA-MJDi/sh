@extends('admin.layouts.app')

@section('page-title', 'تکالیف')
@section('page-description', 'تعریف تکلیف، مشاهده تحویل‌ها و ثبت بازخورد')

@section('content')
    <div class="panel-page-head">
        <div><h2>تکالیف و پاسخ‌ها</h2><p>عملیات روی کلاس‌های مجاز شما انجام می‌شود.</p></div>
        @canany(['homework.manage.all', 'homework.manage.assigned'])
            <button class="panel-btn" type="button" data-admin-drawer-open="homework-create" data-drawer-title="تکلیف جدید">＋ تکلیف جدید</button>
        @endcanany
    </div>

    <section class="panel-grid panel-grid-3">
        <x-admin.stat label="تکلیف‌های این فهرست" :value="number_format($homeworks->total())" note="ثبت‌شده در سامانه" accent="#7b8cff"/>
        <x-admin.stat label="پاسخ‌های دریافت‌شده" :value="number_format($homeworks->sum('submissions_count'))" note="مجموع صفحهٔ جاری" accent="#0b8f80"/>
        <x-admin.stat label="منتظر بررسی" :value="number_format($homeworks->sum('pending_submissions_count'))" note="نیازمند بازخورد مدرس" accent="#f0a21a"/>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>تکالیف ثبت‌شده</h3><p>فهرست تکالیف کلاس‌های قابل دسترس</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>کلاس</th><th>مهلت تحویل</th><th>پاسخ</th><th>در انتظار بررسی</th><th>وضعیت</th><th>پیوست</th></tr></thead>
                <tbody>
                @forelse ($homeworks as $homework)
                    <tr>
                        <td><strong>{{ $homework->title }}</strong><div class="panel-muted">{{ $homework->creator->name ?? '—' }}</div></td>
                        <td>{{ $homework->classroom->name ?? '—' }}</td>
                        <td>{{ $homework->due_at?->format('Y-m-d H:i') ?? 'بدون مهلت' }}</td>
                        <td>{{ number_format($homework->submissions_count) }}</td>
                        <td><x-admin.badge variant="warning">{{ number_format($homework->pending_submissions_count) }}</x-admin.badge></td>
                        <td><x-admin.badge :variant="$homework->status === 'published' ? 'success' : 'warning'">{{ $homework->status === 'published' ? 'منتشرشده' : 'پیش‌نویس' }}</x-admin.badge></td>
                        <td>@if ($homework->attachment_path)<a href="{{ route('admin.homework.attachment', $homework) }}">دریافت فایل</a>@else—@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="panel-empty">تکلیفی برای نمایش وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $homeworks->count() }} از {{ $homeworks->total() }} تکلیف</span>{{ $homeworks->links() }}</div>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>پاسخ‌های دانش‌آموزان</h3><p>ثبت نمره و بازخورد</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>دانش‌آموز</th><th>تکلیف / کلاس</th><th>زمان ارسال</th><th>وضعیت</th>@can('homework.grade')<th>بررسی</th>@endcan</tr></thead>
                <tbody>
                @forelse ($submissions as $submission)
                    <tr>
                        <td>{{ $submission->student->name ?? '—' }}</td>
                        <td>{{ $submission->homework->title ?? '—' }}<div class="panel-muted">{{ $submission->homework->classroom->name ?? '' }}</div></td>
                        <td>{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><x-admin.badge :variant="$submission->status === 'reviewed' ? 'success' : 'warning'">{{ $submission->status === 'reviewed' ? 'بررسی‌شده' : 'منتظر بررسی' }}</x-admin.badge></td>
                        @can('homework.grade')
                            <td><button class="panel-btn panel-btn-secondary panel-btn-sm" type="button" data-admin-drawer-open="submission-review-{{ $submission->id }}" data-drawer-title="بررسی پاسخ">بررسی</button></td>
                        @endcan
                    </tr>
                    @can('homework.grade')
                        <x-admin.drawer-form :id="'submission-review-'.$submission->id" title="بررسی پاسخ" :action="route('admin.homework.review', $submission)" submit-label="ذخیره نتیجه">
                            @method('PATCH')
                            <div class="panel-field"><label>پاسخ دانش‌آموز</label><div class="panel-card">{{ $submission->answer ?: 'پاسخ متنی ثبت نشده است.' }}</div></div>
                            @if ($submission->files->isNotEmpty())
                                <div class="panel-field"><label>فایل‌های پیوست</label>@foreach ($submission->files as $file)<div class="panel-muted">{{ $file->original_name }}</div>@endforeach</div>
                            @endif
                            <div class="panel-field"><label>نمره از ۲۰</label><input class="panel-input" type="number" name="score" min="0" max="20" step=".25" value="{{ $submission->score }}"></div>
                            <div class="panel-field"><label>بازخورد مدرس</label><textarea class="panel-textarea" name="feedback" required maxlength="10000">{{ $submission->feedback }}</textarea></div>
                        </x-admin.drawer-form>
                    @endcan
                @empty
                    <tr><td colspan="5" class="panel-empty">پاسخی برای بررسی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @canany(['homework.manage.all', 'homework.manage.assigned'])
        <x-admin.drawer-form id="homework-create" title="تکلیف جدید" :action="route('admin.homework.store')" submit-label="ثبت تکلیف" enctype="multipart/form-data">
            <div class="panel-field"><label>عنوان</label><input class="panel-input" name="title" required maxlength="180"></div>
            <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id" required><option value="">انتخاب کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>مهلت تحویل</label><input class="panel-input" type="datetime-local" name="due_at"></div>
            <div class="panel-field"><label>دستورالعمل</label><textarea class="panel-textarea" name="instructions" maxlength="20000"></textarea></div>
            <div class="panel-field"><label>پیوست (اختیاری)</label><input class="panel-input" type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"></div>
            <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="published">منتشر شود</option><option value="draft">پیش‌نویس</option></select></div>
        </x-admin.drawer-form>
    @endcanany
@endsection
