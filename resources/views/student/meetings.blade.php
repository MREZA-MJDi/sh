@extends('student.layouts.app')
@section('title', 'جلسات با اولیا')
@section('content')
<section class="page">
    <div class="card">
        <div class="chead"><h2>جلسات با اولیا و آموزشگاه</h2><span class="pill">{{ number_format($meetings->total()) }} جلسه</span></div>
        @php
            $labels = [
                'requested' => ['am', 'درخواست‌شده'],
                'proposed' => ['bl', 'پیشنهاد مدرس'],
                'scheduled' => ['gn', 'زمان‌بندی‌شده'],
                'completed' => ['gn', 'برگزارشده'],
                'declined' => ['rd', 'ردشده'],
                'cancelled' => ['rd', 'لغوشده'],
            ];
        @endphp
        @forelse($meetings as $meeting)
            @php $status = $labels[$meeting->status] ?? ['bl', $meeting->status]; @endphp
            <div class="trk">
                <div>
                    <b>{{ $meeting->title }}</b>
                    <span>{{ $meeting->guardian?->name ?? 'ولی دانش‌آموز' }} · {{ $meeting->classroom?->name ?? 'جلسه مستقل' }}</span>
                    <span>{{ ($meeting->starts_at ?? $meeting->preferred_at) ? \App\Support\PersianDate::format($meeting->starts_at ?? $meeting->preferred_at, 'Y/m/d H:i') : 'زمان تعیین نشده' }}</span>
                    @if($meeting->message)<p>{{ $meeting->message }}</p>@endif
                </div>
                <span class="tag {{ $status[0] }}">{{ $status[1] }}</span>
            </div>
        @empty
            <p>جلسه‌ای برای شما ثبت نشده است.</p>
        @endforelse
        {{ $meetings->links() }}
    </div>
</section>
@endsection
