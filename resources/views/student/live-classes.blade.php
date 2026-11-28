@extends('student.layouts.app')
@section('title', 'کلاس‌های من')
@section('content')
<section class="page" id="live-classes">
<x-portal.page-head icon="calendar" tone="education" title="برنامه کلاس‌های من"/>
<div class="card"><div class="chead"><h2>جلسات پیش‌رو</h2><span class="tag">{{ method_exists($sessions, 'total') ? number_format($sessions->total()) : number_format($sessions->count()) }} جلسه</span></div>
 @forelse (($sessions ?? collect()) as $session)
  @php
    $usesSkyroom = in_array($session->meeting_provider, ['skyroom', null, ''], true);
    $hasJoinLink = filled($session->meeting_url)
        || ($usesSkyroom && (filled($session->classroom?->meeting_url) || ($skyroomReady ?? false)));
  @endphp
  <div class="trk"><div class="portal-state-icon"><x-portal.icon :name="$session->session_type === 'online' ? 'video' : 'calendar'"/></div><div><b>{{ $session->title ?: ($session->classroom?->course?->title ?? 'دوره آموزشی') }}</b><span>{{ $session->classroom?->course?->title ?? 'دوره آموزشی' }} · {{ $session->classroom?->name ?? 'کلاس' }} · {{ in_array($session->session_type, ['onsite', 'in_person'], true) ? 'حضوری' : 'آنلاین' }} · {{ ($session->starts_at ? \App\Support\PersianDate::format($session->starts_at, 'Y/m/d H:i') : '—') }} تا {{ ($session->ends_at ? \App\Support\PersianDate::format($session->ends_at, 'H:i') : '—') }}</span></div>
   @if($session->session_type === 'online')
    @if(!$hasJoinLink)
      <span class="tag">لینک ورود هنوز آماده نیست</span>
    @elseif(!$session->ends_at)
      <span class="tag">زمان پایان جلسه هنوز مشخص نشده است</span>
    @elseif($session->status === 'scheduled' && $session->starts_at && $session->starts_at->lte(now()->addMinutes(15)) && $session->ends_at->gte(now()))
      <form method="POST" action="{{ route('student.class-sessions.join', $session) }}">@csrf<button class="btn s" type="submit"><x-portal.icon name="video"/> ورود به کلاس</button></form>
    @elseif($session->ends_at?->isPast())<span class="tag">پایان جلسه</span>
    @else<span class="tag">ورود از ۱۵ دقیقه قبل</span>@endif
   @else<span class="tag">جلسه حضوری</span>@endif
  </div>
 @empty
  <x-portal.empty-state icon="calendar" title="جلسه‌ی برنامه‌ریزی‌شده‌ای برای شما ثبت نشده است."/>
 @endforelse
 {{ $sessions->links() }}
</div></section>
@endsection
