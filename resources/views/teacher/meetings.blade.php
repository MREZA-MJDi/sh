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
                <i class="bd am">{{ $meeting->status }}</i>
            </div>
        @empty
            <p>درخواست جدیدی ثبت نشده است. ✅</p>
        @endforelse
    </section>
    <section class="c"><h3>جلسه‌های تأییدشده</h3>
        @forelse ($scheduled as $meeting)
            <div class="row"><div>{{ $meeting->guardian?->name }} · {{ $meeting->student?->name }}<small>{{ $meeting->classroom?->name }} · {{ $meeting->title }} · {{ $meeting->starts_at?->format('Y/m/d H:i') }}</small></div><i class="bd gn">تأیید</i></div>
        @empty
            <p>جلسه‌ی تأییدشده‌ای ثبت نشده است.</p>
        @endforelse
    </section>
</div>
{{ $meetings->links() }}
@endsection
