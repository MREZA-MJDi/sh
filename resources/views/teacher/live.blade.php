@extends('teacher.layouts.app')
@section('title', 'کلاس آنلاین')
@section('content')
@php
    $providerNames = ['skyroom' => 'اسکای‌روم', 'bbb' => 'BigBlueButton', 'link' => 'لینک دستی'];
@endphp
<h2>کلاس آنلاین</h2>
<div class="g">
    @if (! $skyroomHealth->available)
        <section class="c" role="status" aria-live="polite">
            <h3>اتصال Skyroom در انتظار فعال‌سازی است</h3>
            <p>زمان‌بندی جلسات، تخصیص مدرس و مدیریت حضور در سامانه فعال است. ورود آنلاین پس از دریافت API و اطلاعات رسمی Skyroom فعال می‌شود؛ تا آن زمان برنامه کلاس‌ها در دسترس باقی می‌ماند.</p>
        </section>
    @endif
    <div class="g g2">
        <section class="c"><h3>روش اتصال</h3>
            <div class="row"><div>۱. زمان‌بندی جلسه<small>جلسات از ساختار کلاس و تقویم آموزشی خوانده می‌شوند.</small></div></div>
            <div class="row"><div>۲. لینک ورود اختصاصی<small>لینک هر جلسه از تنظیمات ثبت‌شده در Backend دریافت می‌شود.</small></div></div>
            <div class="row"><div>۳. بازه زمانی<small>ورود دانش‌آموز فقط در محدوده زمانی مجاز می‌شود.</small></div></div>
        </section>
        <section class="c"><h3>زمان‌بندی کلاس‌ها و لینک‌ها</h3>
            @forelse ($sessions as $session)
                <div class="row">
                    <div>{{ $session->title ?: $session->classroom?->name }}
                        <i class="bd bl">{{ $providerNames[$session->meeting_provider] ?? $session->meeting_provider }}</i>
                        <small>{{ $session->starts_at?->format('Y/m/d H:i') }} · {{ $session->classroom?->name }}</small>
                        @if (! $session->meeting_url && ! $session->classroom?->meeting_url)
                            <small>وضعیت: در انتظار لینک یا فعال‌سازی سرویس ارائه‌دهنده</small>
                        @endif
                    </div>
                    @if ($session->meeting_url)
                        <a class="b s" href="{{ $session->meeting_url }}" target="_blank" rel="noopener noreferrer">ورود</a>
                    @elseif ($session->classroom?->meeting_url)
                        <a class="b s" href="{{ $session->classroom->meeting_url }}" target="_blank" rel="noopener noreferrer">ورود</a>
                    @else
                        <i class="bd am">در انتظار اتصال</i>
                    @endif
                    <form method="POST" action="{{ route('teacher.live.update', $session) }}">
                        @csrf @method('PATCH')
                        <label>سرویس جلسه</label>
                        <select name="meeting_provider">
                            @foreach ($providerNames as $key => $label)
                                <option value="{{ $key }}" @selected($session->meeting_provider === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label>لینک دستی (در صورت وجود)</label>
                        <input type="url" name="meeting_url" value="{{ $session->meeting_url }}" placeholder="https://...">
                        <button class="b s" type="submit">ذخیره تنظیمات همین جلسه</button>
                    </form>
                </div>
            @empty
                <p>جلسه آنلاین آینده‌ای برای کلاس‌های شما ثبت نشده است.</p>
            @endforelse
        </section>
    </div>
    {{ $sessions->links() }}
</div>
@endsection