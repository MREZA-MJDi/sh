@extends('teacher.layouts.app')
@section('title', 'جلسات با اولیا')
@section('content')
<h2>جلسات با اولیا</h2>
@php
    $requests = $meetings->getCollection()->whereIn('status', ['requested', 'proposed']);
    $scheduled = $meetings->getCollection()->where('status', 'scheduled');
@endphp
<div class="g g2">
    <section class="c"><h3>درخواست‌های جدید</h3>
        @forelse ($requests as $meeting)
            <div class="row"><div>{{ $meeting->guardian?->name }} · {{ $meeting->student?->name }}<small>{{ $meeting->classroom?->name }} · {{ $meeting->title }} · {{ ($meeting->starts_at ?? $meeting->preferred_at)?->format('Y/m/d H:i') }}</small></div>
                <i class="bd am">{{ ['requested' => 'درخواست جدید', 'proposed' => 'پیشنهادشده', 'scheduled' => 'زمان‌بندی‌شده', 'declined' => 'ردشده', 'completed' => 'برگزارشده', 'cancelled' => 'لغوشده'][$meeting->status] ?? $meeting->status }}</i>
                <form method="POST" action="{{ route('teacher.meetings.update', $meeting) }}" class="row">
                    @csrf @method('PATCH')
                    <input type="datetime-local" name="starts_at" value="{{ ($meeting->starts_at ?? $meeting->preferred_at)?->format('Y-m-d\\TH:i') }}" aria-label="زمان جلسه">
                    <input type="datetime-local" name="ends_at" value="{{ $meeting->ends_at?->format('Y-m-d\\TH:i') }}" aria-label="پایان جلسه">
                    <button class="b s" type="submit" name="status" value="scheduled">تأیید و زمان‌بندی</button>
                    <button class="b s" type="submit" name="status" value="declined">رد درخواست</button>
                </form>
            </div>
        @empty
            <p>درخواست جدیدی ثبت نشده است. ✅</p>
        @endforelse
    </section>
    <section class="c"><h3>جلسه‌های تأییدشده</h3>
        @forelse ($scheduled as $meeting)
            <div class="row"><div>{{ $meeting->guardian?->name }} · {{ $meeting->student?->name }}<small>{{ $meeting->classroom?->name }} · {{ $meeting->title }} · {{ $meeting->starts_at?->format('Y/m/d H:i') }}</small></div><i class="bd gn">تأیید</i>
                <form method="POST" action="{{ route('teacher.meetings.update', $meeting) }}" class="row">
                    @csrf @method('PATCH')
                    <button class="b s" type="submit" name="status" value="completed">ثبت برگزاری</button>
                    <button class="b s" type="submit" name="status" value="cancelled">لغو جلسه</button>
                </form></div>
        @empty
            <p>جلسه‌ی تأییدشده‌ای ثبت نشده است.</p>
        @endforelse
    </section>
</div>
{{ $meetings->links() }}
@endsection
