@extends('admin.layouts.app')

@section('page-title', 'جلسات والدین')
@section('page-description', 'ثبت درخواست والدین، پیشنهاد مدرس و زمان‌بندی جلسه با خانواده')

@section('content')
    <div class="panel-page-head" data-tone="learning">
        <div><h2>جلسات والدین</h2><p>درخواست‌های والدین و پیشنهادهای مدرس را بررسی و زمان‌بندی کنید.</p></div>
    </div>

    <section class="panel-card" data-tone="learning">
        <div class="panel-card-title"><div><h3>ثبت جلسه جدید</h3><p>مدرس پیشنهاد جلسه می‌دهد؛ مدیر می‌تواند جلسه را نهایی کند.</p></div></div>
        <form method="POST" action="{{ route('admin.parent-meetings.store') }}" class="panel-form-grid">
            @csrf
            <label>کلاس
                <select name="classroom_id">
                    <option value="">بدون کلاس مشخص</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}{{ $classroom->course ? ' · '.$classroom->course->title : '' }}</option>
                    @endforeach
                </select>
            </label>
            <label>دانش‌آموز
                <select name="student_id" required>
                    <option value="">انتخاب دانش‌آموز</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>ولی
                <select name="guardian_id" required>
                    <option value="">شناسه ولی مرتبط با دانش‌آموز را وارد کنید</option>
                    @foreach ($students as $student)
                        @foreach ($student->guardians as $guardian)
                            <option value="{{ $guardian->id }}" @selected(old('guardian_id') == $guardian->id)>{{ $guardian->name }} — ولیِ {{ $student->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </label>
            <label>موضوع
                <input name="title" value="{{ old('title') }}" minlength="3" maxlength="180" required>
            </label>
            <label>شروع جلسه
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required>
            </label>
            <label>پایان جلسه
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}">
            </label>
            <label class="full">توضیحات
                <textarea name="message" rows="2" maxlength="2000">{{ old('message') }}</textarea>
            </label>
            <div><button class="panel-btn" type="submit">ثبت / پیشنهاد جلسه</button></div>
        </form>
    </section>

    <section class="panel-card" data-tone="learning">
        <div class="panel-card-title"><div><h3>فهرست جلسات</h3><p>جلسات پیشنهادی و درخواست‌های والدین در اولویت بررسی قرار دارند.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table">
                <thead><tr><th>دانش‌آموز / ولی</th><th>موضوع</th><th>کلاس</th><th>زمان</th><th>وضعیت</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($meetings as $meeting)
                    @php
                        $labels = ['requested' => 'در انتظار بررسی', 'proposed' => 'پیشنهاد مدرس', 'scheduled' => 'زمان‌بندی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده', 'declined' => 'ردشده'];
                        $terminal = in_array($meeting->status, ['completed', 'cancelled', 'declined'], true);
                    @endphp
                    <tr>
                        <td><strong>{{ $meeting->student?->name ?? '—' }}</strong><div class="panel-muted">{{ $meeting->guardian?->name ?? '—' }}</div></td>
                        <td>{{ $meeting->title }}@if($meeting->message)<div class="panel-muted">{{ $meeting->message }}</div>@endif</td>
                        <td>{{ $meeting->classroom?->name ?? 'عمومی' }}</td>
                        <td>{{ $meeting->starts_at?->format('Y/m/d H:i') ?? ($meeting->preferred_at?->format('Y/m/d H:i') ?? 'تعیین نشده') }}</td>
                        <td>{{ $labels[$meeting->status] ?? $meeting->status }}</td>
                        <td>
                            @if (!$terminal)
                                <form method="POST" action="{{ route('admin.parent-meetings.status', $meeting->id) }}" class="panel-inline-form">
                                    @csrf @method('PATCH')
                                    <select name="status" required>
                                        @if (in_array($meeting->status, ['requested', 'proposed'], true))
                                            <option value="scheduled">تأیید و زمان‌بندی</option>
                                            <option value="declined">رد درخواست</option>
                                        @else
                                            <option value="completed">برگزار شد</option>
                                            <option value="cancelled">لغو شد</option>
                                        @endif
                                    </select>
                                    @if (in_array($meeting->status, ['requested', 'proposed'], true))
                                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" aria-label="زمان شروع نهایی">
                                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" aria-label="زمان پایان">
                                    @endif
                                    <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">ثبت</button>
                                </form>
                            @else
                                <span class="panel-muted">بسته‌شده</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">هنوز جلسه‌ای ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination"><span>{{ $meetings->count() }} از {{ $meetings->total() }} جلسه</span>{{ $meetings->links() }}</div>
    </section>
@endsection