@extends('teacher.layouts.app')
@section('title', 'کلاس آنلاین')
@section('content')
@php
    $providerNames = ['skyroom' => 'اسکای‌روم', 'jitsi' => 'Jitsi Meet', 'bbb' => 'BigBlueButton', 'link' => 'لینک دستی'];
@endphp
<header class="teacher-page-head teacher-live-head">
    <div><span class="teacher-eyebrow">آموزش زنده</span><h1>کلاس آنلاین</h1><p>جلسه‌های آنلاین دوره‌ها و کلاس‌هایی که به شما تخصیص داده شده‌اند.</p></div>
    <a class="b s" href="{{ route('teacher.classes') }}">مشاهده کلاس‌های من</a>
</header>
@if (! $skyroomHealth->available)
<section class="teacher-live-notice" role="status" aria-live="polite"><span class="teacher-live-notice-icon" aria-hidden="true">ⓘ</span><div><strong>اتصال Skyroom در انتظار فعال‌سازی است</strong><p>برنامه‌ریزی جلسه‌ها و لینک‌های دستی فعال‌اند. ورود خودکار Skyroom پس از ثبت API رسمی سرویس‌دهنده فعال می‌شود.</p></div></section>
@endif
<section class="teacher-live-stats" aria-label="خلاصه جلسه‌ها">
    <article class="teacher-live-stat"><span class="teacher-live-stat-icon">●</span><div><strong>{{ $liveCount }}</strong><span>در حال برگزاری</span></div></article>
    <article class="teacher-live-stat"><span class="teacher-live-stat-icon">◷</span><div><strong>{{ $upcomingCount }}</strong><span>جلسه پیش‌رو</span></div></article>
    <article class="teacher-live-stat"><span class="teacher-live-stat-icon">↗</span><div><strong>{{ $waitingCount }}</strong><span>در انتظار لینک</span></div></article>
</section>
<section class="c teacher-live-filter-card">
    <div class="teacher-section-head"><div><h2>فهرست جلسه‌ها</h2><p>فقط جلسه‌های آینده و جلسه‌های در حال برگزاری نمایش داده می‌شوند.</p></div></div>
    <form method="GET" action="{{ route('teacher.live') }}" class="teacher-live-filters">
        <label><span>کلاس</span><select name="classroom_id"><option value="">همه کلاس‌های من</option>@foreach ($assignedClassrooms as $classroom)<option value="{{ $classroom->id }}" @selected((int) ($filters['classroom_id'] ?? 0) === (int) $classroom->id)>{{ $classroom->name }}</option>@endforeach</select></label>
        <label><span>جست‌وجوی جلسه یا کلاس</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="عنوان جلسه یا نام کلاس"></label>
        <label><span>مرتب‌سازی</span><select name="sort"><option value="soonest" @selected(($filters['sort'] ?? 'soonest') === 'soonest')>نزدیک‌ترین جلسه</option><option value="latest" @selected(($filters['sort'] ?? '') === 'latest')>دیرترین جلسه</option></select></label>
        <label><span>سرویس برگزاری</span><select name="provider"><option value="">همه سرویس‌ها</option>@foreach ($providerNames as $key => $label)<option value="{{ $key }}" @selected(($filters['provider'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
        <div class="teacher-live-filter-actions"><button class="b" type="submit">اعمال فیلتر</button><a class="b s" href="{{ route('teacher.live') }}">پاک‌کردن</a></div>
    </form>
</section>
<section class="teacher-live-session-list" aria-label="جلسه‌های آنلاین">
@forelse ($sessions as $session)
    @php
        $isLive = $session->starts_at && $session->starts_at->lte(now()) && (! $session->ends_at || $session->ends_at->gte(now()));
        $joinUrl = $session->meeting_url ?: $session->classroom?->meeting_url;
        if (! is_string($joinUrl) || ! str_starts_with(strtolower($joinUrl), 'https://')) {
            $joinUrl = null;
        }
        $provider = $providerNames[$session->meeting_provider] ?? ($session->meeting_provider ?: 'نامشخص');
    @endphp
    <article class="c teacher-live-session-card">
        <div class="teacher-live-session-main">
            <div class="teacher-live-session-title"><span class="teacher-live-session-symbol" aria-hidden="true">🎥</span><div><h3>{{ $session->title ?: $session->classroom?->name ?: 'جلسه آنلاین' }}</h3><p>{{ $session->classroom?->name }} · {{ $provider }}</p></div></div>
            <span class="bd {{ $isLive ? 'gn' : ($joinUrl ? 'bl' : 'am') }}">{{ $isLive ? 'در حال برگزاری' : ($joinUrl ? 'آماده ورود' : 'در انتظار لینک') }}</span>
        </div>
        <div class="teacher-live-session-meta">
            <div><span>شروع</span><strong><time data-jalali-display="{{ $session->starts_at?->format('Y-m-d H:i') }}">{{ $session->starts_at ? \App\Support\PersianDate::format($session->starts_at, 'Y/m/d H:i') : '—' }}</time></strong></div>
            <div><span>پایان</span><strong><time data-jalali-display="{{ $session->ends_at?->format('Y-m-d H:i') }}">{{ $session->ends_at ? \App\Support\PersianDate::format($session->ends_at, 'Y/m/d H:i') : 'ثبت نشده' }}</time></strong></div>
            <div><span>وضعیت سرویس</span><strong>{{ $session->classroom?->skyroom_status === 'ready' ? 'اتاق آماده' : ($session->classroom?->skyroom_status === 'waiting_contract' ? 'در انتظار قرارداد' : $provider) }}</strong></div>
        </div>
        <div class="teacher-live-session-actions">
            @if ($joinUrl)<a class="b" href="{{ $joinUrl }}" target="_blank" rel="noopener noreferrer">ورود به کلاس</a>@else<span class="teacher-live-no-link">لینک ورود برای این جلسه ثبت نشده است.</span>@endif
            @if (auth()->user()->hasPermissionTo('classes.manage.assigned'))
            <details class="teacher-live-settings"><summary>تنظیمات اتصال جلسه</summary>
                <form method="POST" action="{{ route('teacher.live.update', $session) }}" class="teacher-form teacher-live-settings-form">
                    @csrf @method('PATCH')
                    <label>سرویس برگزاری</label><select name="meeting_provider" required>@foreach ($providerNames as $key => $label)<option value="{{ $key }}" @selected($session->meeting_provider === $key)>{{ $label }}</option>@endforeach</select>
                    <label>لینک امن جلسه (HTTPS)</label><input type="url" name="meeting_url" value="{{ $session->meeting_url }}" placeholder="https://..." inputmode="url">
                    <button class="b s" type="submit">ذخیره تنظیمات</button>
                </form>
            </details>
            @endif
        </div>
    </article>
@empty
    <div class="c teacher-live-empty"><span aria-hidden="true">🎥</span><h3>جلسه آنلاینی پیدا نشد</h3><p>برای کلاس‌های تخصیص‌یافته شما جلسه آنلاین آینده‌ای ثبت نشده یا فیلترها نتیجه‌ای نداشته‌اند.</p><a class="b s" href="{{ route('teacher.live') }}">نمایش همه جلسه‌ها</a></div>
@endforelse
</section>
{{ $sessions->links() }}
@endsection
