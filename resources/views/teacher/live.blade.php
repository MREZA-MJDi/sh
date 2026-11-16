@extends('teacher.layouts.app')
@section('title', 'کلاس آنلاین')
@section('content')
@php
    $providerNames = ['skyroom' => 'اسکای‌روم', 'bbb' => 'BigBlueButton', 'link' => 'لینک دستی'];
    $firstSession = $sessions->getCollection()->first();
    $currentProvider = $firstSession?->meeting_provider ?? 'link';
@endphp
<h2>کلاس آنلاین</h2>
<div class="g">
    <div class="g g2">
        <section class="c"><h3>سرویس کلاس</h3>
            @if ($firstSession)
                <form method="POST" action="{{ route('teacher.live.update', $firstSession) }}">
                    @csrf @method('PATCH')
                    <label>سرویس جلسه</label>
                    <div class="chips">
                        @foreach ($providerNames as $key => $label)
                            <label class="chip"><input type="radio" name="meeting_provider" value="{{ $key }}" @checked($currentProvider === $key)>{{ $label }}</label>
                        @endforeach
                    </div>
                    <label>لینک جلسه</label>
                    <input type="url" name="meeting_url" value="{{ $firstSession->meeting_url }}" placeholder="https://...">
                    <button class="b s" type="submit">ذخیره اتصال</button>
                </form>
            @else
                <span class="chip">جلسه‌ای برای تنظیم وجود ندارد</span>
            @endif
            <div class="frame"><div>اتاق کلاس<br><small>{{ $firstSession?->classroom?->name ?? 'جلسه‌ای برای نمایش وجود ندارد' }}</small></div></div>
        </section>
        <section class="c"><h3>روش اتصال</h3>
            <div class="row"><div>۱. هر کلاس یک اتاق دارد<small>شناسه اتاق از تنظیمات همان جلسه خوانده می‌شود</small></div></div>
            <div class="row"><div>۲. لینک ورود اختصاصی<small>لینک هر جلسه از تنظیمات ثبت‌شده در Backend دریافت می‌شود</small></div></div>
            <div class="row"><div>۳. بازه زمانی<small>جلسات فقط در محدوده زمانی تعریف‌شده فعال هستند</small></div></div>
        </section>
        <section class="c"><h3>زمان‌بندی کلاس‌ها و لینک‌ها</h3>
            @forelse ($sessions as $session)
                <div class="row"><div>{{ $session->title ?: $session->classroom?->name }} <i class="bd bl">{{ $providerNames[$session->meeting_provider] ?? $session->meeting_provider }}</i><small>{{ $session->starts_at?->format('Y/m/d H:i') }} · {{ $session->classroom?->name }}</small></div>
                    @if ($session->meeting_url)<a class="b s" href="{{ $session->meeting_url }}" target="_blank" rel="noopener">ورود</a>
                    @elseif ($session->classroom?->meeting_url)<a class="b s" href="{{ $session->classroom->meeting_url }}" target="_blank" rel="noopener">ورود</a>
                    @else<i class="bd am">لینک ثبت نشده</i>@endif
                </div>
            @empty
                <p>جلسه‌ای برای آینده ثبت نشده است.</p>
            @endforelse
        </section>
    </div>
    {{ $sessions->links() }}
</div>
@endsection
