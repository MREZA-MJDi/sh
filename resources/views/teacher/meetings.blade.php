@extends('teacher.layouts.app')
@section('title', 'جلسات با اولیا')
@section('content')
<div class="teacher-page-head"><div><span class="teacher-eyebrow">ارتباط با خانواده</span><h1>جلسات با اولیا</h1><p>درخواست‌ها و زمان‌بندی جلسه‌های مربوط به کلاس‌های شما.</p></div></div>
@php
    $requests = $meetings->getCollection()->whereIn('status', ['requested', 'proposed']);
    $scheduled = $meetings->getCollection()->where('status', 'scheduled');
@endphp
<div class="g g2">
    <section class="c"><h3>درخواست‌های جدید</h3>
        @forelse ($requests as $meeting)
            <div class="row">
                <div>{{ $meeting->guardian?->name }} · {{ $meeting->student?->name }}<small>{{ $meeting->classroom?->name }} · {{ $meeting->title }} · <time data-jalali-display="{{ ($meeting->starts_at ?? $meeting->preferred_at)?->format('Y-m-d H:i') }}">{{ ($meeting->starts_at ?? $meeting->preferred_at)?->format('Y/m/d H:i') }}</time></small></div>
                <i class="bd am">{{ ['requested' => 'درخواست جدید', 'proposed' => 'پیشنهادشده', 'scheduled' => 'زمان‌بندی‌شده', 'declined' => 'ردشده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده'][$meeting->status] ?? $meeting->status }}</i>
                @if(auth()->user()->hasPermissionTo('classes.manage.assigned'))
                    <form method="POST" action="{{ route('teacher.meetings.update', $meeting) }}" class="teacher-meeting-actions">
                        @csrf @method('PATCH')
                        <input type="datetime-local" name="starts_at" value="{{ ($meeting->starts_at ?? $meeting->preferred_at)?->format('Y-m-d\\TH:i') }}" aria-label="زمان جلسه">
                        <input type="datetime-local" name="ends_at" value="{{ $meeting->ends_at?->format('Y-m-d\\TH:i') }}" aria-label="پایان جلسه">
                        <button class="b s" type="submit" name="status" value="scheduled">تأیید و زمان‌بندی</button>
                        <button class="b s" type="submit" name="status" value="declined">رد درخواست</button>
                    </form>
                @else
                    <span class="bd am">فقط مشاهده</span>
                @endif
            </div>
        @empty
            <p>درخواست جدیدی ثبت نشده است. ✅</p>
        @endforelse
    </section>
    <section class="c"><h3>جلسه‌های تأییدشده</h3>
        @forelse ($scheduled as $meeting)
            <div class="row">
                <div>{{ $meeting->guardian?->name }} · {{ $meeting->student?->name }}<small>{{ $meeting->classroom?->name }} · {{ $meeting->title }} · <time data-jalali-display="{{ $meeting->starts_at?->format('Y-m-d H:i') }}">{{ $meeting->starts_at?->format('Y/m/d H:i') }}</time></small></div>
                <i class="bd gn">تأیید</i>
                @if(auth()->user()->hasPermissionTo('classes.manage.assigned'))
                    <form method="POST" action="{{ route('teacher.meetings.update', $meeting) }}" class="teacher-meeting-actions">
                        @csrf @method('PATCH')
                        <button class="b s" type="submit" name="status" value="completed">ثبت برگزاری</button>
                        <button class="b s" type="submit" name="status" value="cancelled">لغو جلسه</button>
                    </form>
                @else
                    <span class="bd am">فقط مشاهده</span>
                @endif
            </div>
        @empty
            <p>جلسه‌ی تأییدشده‌ای ثبت نشده است.</p>
        @endforelse
    </section>
</div>
{{ $meetings->links() }}
@endsection
