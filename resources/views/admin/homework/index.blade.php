@extends('layouts.panel')

@section('page-title', 'تکالیف و بررسی')
@section('page-description', 'تعریف تکلیف برای کلاس و بررسی پاسخ‌های ارسال‌شده')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>تعریف تکلیف جدید</h3><p>فایل پیوست در فضای خصوصی ذخیره می‌شود و از مسیر محافظت‌شده در دسترس مدیر قرار می‌گیرد.</p></div></div>
        <form method="POST" action="{{ route('admin.homework.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان تکلیف</label><input class="panel-input" name="title" value="{{ old('title') }}" required maxlength="180"></div>
                <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id" required><option value="">انتخاب کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>مهلت تحویل</label><input class="panel-input" type="datetime-local" name="due_at" value="{{ old('due_at') }}"></div>
                <div class="panel-field"><label>فایل پیوست</label><input class="panel-input" type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"></div>
                <div class="panel-field"><label>وضعیت انتشار</label><select class="panel-select" name="status"><option value="published">منتشر شود</option><option value="draft">پیش‌نویس</option></select></div>
                <div class="panel-field"><label>دستورالعمل</label><input class="panel-input" name="instructions" value="{{ old('instructions') }}" maxlength="20000"></div>
            </div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت تکلیف</button></div>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>تکالیف ثبت‌شده</h3><p>تعداد پاسخ‌ها و موارد منتظر بررسی از دیتابیس محاسبه می‌شود.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>کلاس</th><th>مهلت</th><th>پاسخ‌ها</th><th>منتظر بررسی</th><th>وضعیت</th><th>پیوست</th></tr></thead>
                <tbody>
                @forelse ($homeworks as $homework)
                    <tr>
                        <td><strong>{{ $homework->title }}</strong><div class="panel-muted">{{ $homework->creator->name ?? '—' }}</div></td>
                        <td>{{ $homework->classroom->name ?? '—' }}</td>
                        <td>{{ $homework->due_at?->format('Y-m-d H:i') ?? 'بدون مهلت' }}</td>
                        <td>{{ $homework->submissions_count }}</td>
                        <td><span class="panel-badge panel-badge-warning">{{ $homework->pending_submissions_count }}</span></td>
                        <td><span class="panel-badge {{ $homework->status==='published' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ $homework->status==='published' ? 'منتشرشده' : 'پیش‌نویس' }}</span></td>
                        <td>@if ($homework->attachment_path)<a class="panel-muted" href="{{ route('admin.homework.attachment', $homework) }}">دریافت فایل</a>@else—@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="panel-empty">تکلیفی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $homeworks->count() }} از {{ $homeworks->total() }} تکلیف</span>{{ $homeworks->links() }}</div>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>پاسخ‌های دانش‌آموزان</h3><p>ثبت نمره و بازخورد برای هر ارسال</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>دانش‌آموز</th><th>تکلیف</th><th>تاریخ ارسال</th><th>وضعیت</th><th>بررسی</th></tr></thead>
                <tbody>
                @forelse ($submissions as $submission)
                    <tr>
                        <td>{{ $submission->student->name ?? '—' }}</td>
                        <td>{{ $submission->homework->title ?? '—' }}<div class="panel-muted">{{ $submission->homework->classroom->name ?? '' }}</div></td>
                        <td>{{ $submission->submitted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td><span class="panel-badge {{ $submission->status==='reviewed' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ $submission->status==='reviewed' ? 'بررسی‌شده' : 'منتظر بررسی' }}</span></td>
                        <td>
                            <details class="panel-details">
                                <summary>{{ $submission->status==='reviewed' ? 'ویرایش بازخورد' : 'بررسی پاسخ' }}</summary>
                                <div class="panel-details-content">
                                    <p class="panel-muted mb-2">{{ $submission->answer ?: 'پاسخ متنی ثبت نشده است.' }}</p>
                                    <form method="POST" action="{{ route('admin.homework.review', $submission) }}" class="grid gap-2">
                                        @csrf @method('PATCH')
                                        <div class="panel-field"><label>نمره از ۲۰</label><input class="panel-input" type="number" name="score" min="0" max="20" step=".25" value="{{ $submission->score }}"></div>
                                        <div class="panel-field"><label>بازخورد</label><textarea class="panel-textarea" name="feedback" required>{{ $submission->feedback }}</textarea></div>
                                        <button class="panel-btn panel-btn-sm" type="submit">ذخیره نتیجه</button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="panel-empty">هنوز پاسخی برای تکالیف ارسال نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
