@extends('layouts.parent')

@section('title', 'جلسات با آموزشگاه')

@section('content')
    <section class="page-head">
        <div>
            <span class="eyebrow">ارتباط با آموزشگاه</span>
            <h1>جلسات با آموزشگاه</h1>
            <p class="mu">درخواست جلسه ثبت کنید و زمان‌بندی یا نتیجه‌ی آن را پیگیری کنید.</p>
        </div>
    </section>

    <section class="card">
        <div class="chead"><h2>ثبت درخواست جلسه</h2></div>
        @if ($children->isEmpty())
            <div class="empty">برای این حساب فرزند فعالی متصل نشده است.</div>
        @else
            <form method="POST" action="{{ route('guardian.meetings.store') }}" class="form-grid">
                @csrf
                <label>فرزند
                    <select name="student_id" required>
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}" @selected(old('student_id') == $child->id)>{{ $child->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label>کلاس مرتبط (اختیاری)
                    <select name="classroom_id">
                        <option value="">بدون انتخاب کلاس مشخص</option>
                        @foreach ($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected(old('classroom_id') == $classroom->id)>{{ $classroom->name }}{{ $classroom->course ? ' · '.$classroom->course->title : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>موضوع جلسه
                    <input name="title" value="{{ old('title') }}" minlength="3" maxlength="180" required>
                </label>
                <label>زمان پیشنهادی (اختیاری)
                    <input type="datetime-local" name="preferred_at" value="{{ old('preferred_at') }}">
                </label>
                <label class="full">توضیحات
                    <textarea name="message" rows="3" maxlength="2000">{{ old('message') }}</textarea>
                </label>
                <div><button class="btn" type="submit">ثبت درخواست</button></div>
            </form>
        @endif
    </section>

    <section class="card">
        <div class="chead"><h2>درخواست‌ها و زمان‌بندی‌ها</h2><span class="pill">{{ $meetings->total() }} مورد</span></div>
        <div class="tw">
            <table class="tbl">
                <thead><tr><th>فرزند</th><th>موضوع</th><th>کلاس</th><th>زمان</th><th>وضعیت</th><th>توضیحات</th></tr></thead>
                <tbody>
                @forelse ($meetings as $meeting)
                    @php
                        $labels = ['requested' => 'در انتظار بررسی', 'proposed' => 'پیشنهاد زمان', 'scheduled' => 'زمان‌بندی‌شده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده', 'declined' => 'ردشده'];
                    @endphp
                    <tr>
                        <td>{{ $meeting->student?->name ?? '—' }}</td>
                        <td>{{ $meeting->title }}</td>
                        <td>{{ $meeting->classroom?->name ?? 'عمومی' }}</td>
                        <td>
                            @if ($meeting->starts_at)
                                {{ \App\Support\PersianDate::format($meeting->starts_at, 'Y/m/d H:i') }}
                            @elseif ($meeting->preferred_at)
                                <span class="mu">پیشنهاد شما: {{ \App\Support\PersianDate::format($meeting->preferred_at, 'Y/m/d H:i') }}</span>
                            @else
                                پس از بررسی اعلام می‌شود
                            @endif
                            @if ($meeting->ends_at)<div class="mu">تا {{ \App\Support\PersianDate::format($meeting->ends_at, 'H:i') }}</div>@endif
                        </td>
                        <td><span class="pill">{{ $labels[$meeting->status] ?? $meeting->status }}</span></td>
                        <td>{{ $meeting->message ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">هنوز درخواستی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $meetings->links() }}
    </section>
@endsection