@extends('admin.layouts.app')
@section('title', 'حضور و غیاب')

@section('content')
@php
    $marks = ['present' => 'حاضر', 'late' => 'تأخیر', 'absent' => 'غایب', 'excused' => 'موجه'];
@endphp

<x-admin.page-head tone="track" icon="check" title="حضور و غیاب" desc="ثبت و بررسی حضور واقعی دانش‌آموزان در جلسات برگزارشده"/>

<x-admin.panel title="انتخاب کلاس و جلسه" data-tone="track">
    <form method="GET" action="{{ route('admin.attendance.index') }}" class="adm-form">
        <x-admin.field label="کلاس" name="classroom_id">
            <select class="adm-select" name="classroom_id" required>
                @foreach($classrooms as $item)
                    <option value="{{ $item->id }}" @selected($classroom?->id === $item->id)>{{ $item->name }}</option>
                @endforeach
            </select>
        </x-admin.field>
        <x-admin.field label="تاریخ جلسه" name="date">
            <input class="adm-input" type="date" name="date" value="{{ $date }}" required>
        </x-admin.field>
        <x-admin.field label="جلسه برگزارشده" name="class_session_id">
            <select class="adm-select" name="class_session_id">
                <option value="">اولین جلسه این روز</option>
                @foreach($sessions as $item)
                    <option value="{{ $item->id }}" @selected($session?->id === $item->id)>
                        {{ $item->title ?: 'جلسه' }} · {{ $item->starts_at?->format('H:i') }}
                    </option>
                @endforeach
            </select>
        </x-admin.field>
        <div class="wide">
            <button class="adm-btn adm-btn-primary" type="submit">نمایش فهرست کلاس</button>
        </div>
    </form>
</x-admin.panel>

@if(!$classroom)
    <x-admin.empty text="کلاسی برای این حساب کاربری در دسترس نیست."/>
@elseif(!$session)
    <x-admin.empty text="برای تاریخ انتخاب‌شده جلسه‌ای پیدا نشد. ابتدا جلسه را از بخش کلاس‌ها زمان‌بندی کنید."/>
@else
    <x-admin.panel title="{{ $classroom->name }} · {{ $session->title ?: 'جلسه' }}" desc="شروع: {{ $session->starts_at?->format('Y/m/d H:i') }} · پایان: {{ $session->ends_at?->format('H:i') }}" flush data-tone="track">
        <form method="POST" action="{{ route('admin.attendance.store') }}">
            @csrf
            <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
            <input type="hidden" name="class_session_id" value="{{ $session->id }}">
            <div class="adm-table-wrap">
                <table class="adm-table">
                    <thead>
                        <tr><th>دانش‌آموز</th><th>وضعیت حضور</th><th>زمان ورود</th><th>مدت حضور (دقیقه)</th><th>یادداشت</th></tr>
                    </thead>
                    <tbody>
                        @forelse($classroom->students as $student)
                            @php $record = $records->get($student->id); @endphp
                            <tr>
                                <td><b>{{ $student->name }}</b></td>
                                <td>
                                    <select class="adm-select" name="attendance[{{ $student->id }}][status]" required>
                                        <option value="">انتخاب وضعیت</option>
                                        @foreach($marks as $value => $label)
                                            <option value="{{ $value }}" @selected($record?->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input class="adm-input" type="datetime-local"
                                           name="attendance[{{ $student->id }}][joined_at]"
                                           value="{{ $record?->joined_at?->format('Y-m-d\TH:i') }}">
                                </td>
                                <td>
                                    <input class="adm-input" type="number" min="0" max="1440"
                                           name="attendance[{{ $student->id }}][attended_minutes]"
                                           value="{{ $record?->attended_minutes }}" placeholder="دقیقه">
                                </td>
                                <td>
                                    <input class="adm-input" name="attendance[{{ $student->id }}][note]"
                                           value="{{ $record?->note }}" maxlength="500" placeholder="اختیاری">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">دانش‌آموزی در این کلاس ثبت‌نام نشده است.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($classroom->students->isNotEmpty())
                <div class="adm-panel-foot">
                    <button class="adm-btn adm-btn-primary" type="submit">ذخیره حضور و غیاب جلسه</button>
                </div>
            @endif
        </form>
    </x-admin.panel>
@endif

<x-admin.panel title="سوابق ثبت‌شده" desc="سوابق بر اساس فیلتر کلاس، جلسه و تاریخ بالا محدود شده‌اند." flush>
    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr><th>تاریخ و جلسه</th><th>دانش‌آموز</th><th>کلاس</th><th>وضعیت</th><th>مدت حضور</th><th>ثبت‌کننده</th></tr>
            </thead>
            <tbody>
                @forelse($history as $record)
                    @php
                        $labels = ['present' => ['ok', 'حاضر'], 'late' => ['warn', 'تأخیر'], 'absent' => ['bad', 'غایب'], 'excused' => ['info', 'موجه']];
                        $label = $labels[$record->status] ?? ['mute', $record->status];
                    @endphp
                    <tr>
                        <td>{{ $record->session_date?->format('Y/m/d') }}<small>{{ $record->session?->starts_at?->format('H:i') }}</small></td>
                        <td>{{ $record->student?->name ?? '—' }}</td>
                        <td>{{ $record->classroom?->name ?? '—' }}</td>
                        <td><x-admin.badge :tone="$label[0]">{{ $label[1] }}</x-admin.badge></td>
                        <td class="adm-num">{{ $record->attended_minutes === null ? '—' : number_format($record->attended_minutes).' دقیقه' }}</td>
                        <td>{{ $record->marker?->name ?? 'ثبت سیستمی' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">برای فیلتر انتخاب‌شده سابقه‌ای ثبت نشده است.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-slot:footer>{{ $history->links() }}</x-slot:footer>
</x-admin.panel>
@endsection
