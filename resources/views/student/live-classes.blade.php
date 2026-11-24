@extends('student.layouts.app')
@section('title', 'کلاس‌های من')
@section('content')
<section class="page" id="live-classes"><div class="card"><div class="chead"><h2>جلسات پیش‌رو</h2><span class="tag">{{ method_exists($sessions, 'total') ? number_format($sessions->total()) : number_format($sessions->count()) }} جلسه</span></div>
 @forelse (($sessions ?? collect()) as $session)
  <div class="trk"><div class="th"></div><div><b>{{ $session->title ?: ($session->classroom?->course?->title ?? 'دوره آموزشی') }}</b><span>{{ $session->classroom?->course?->title ?? 'دوره آموزشی' }} · {{ $session->classroom?->name ?? 'کلاس' }} · {{ in_array($session->session_type, ['onsite', 'in_person'], true) ? 'حضوری' : 'آنلاین' }} · {{ ($session->starts_at ? \App\Support\PersianDate::format($session->starts_at, 'Y/m/d H:i') : '—') }} تا {{ ($session->ends_at ? \App\Support\PersianDate::format($session->ends_at, 'H:i') : '—') }}</span></div>
   @if ($session->session_type === 'online' && ! $session->meeting_url && ! $session->classroom?->meeting_url)
    <span class="tag">{{ $session->meeting_provider === 'skyroom' ? 'اتصال Skyroom در انتظار فعال‌سازی' : 'لینک ورود هنوز ثبت نشده' }}</span>
   @endif
   @if ($session->session_type === 'online')<form method="POST" action="{{ route('student.class-sessions.join', $session) }}">@csrf<button class="btn s" type="submit">بررسی وضعیت ورود</button></form>@else<span class="tag">جلسه حضوری</span>@endif
  </div>
 @empty
  <p>جلسه‌ی برنامه‌ریزی‌شده‌ای برای شما ثبت نشده است.</p>
 @endforelse
 {{ $sessions->links() }}
</div></section>
@endsection