@extends('student.layouts.app')
@section('title', 'کلاس‌های من')
@section('content')
<section class="page" id="live-classes"><div class="card"><div class="chead"><h2>جلسات پیش‌رو</h2><span class="tag">{{ method_exists($sessions, 'total') ? number_format($sessions->total()) : number_format($sessions->count()) }} جلسه</span></div>
 @forelse (($sessions ?? collect()) as $session)
  <div class="trk"><div class="th"></div><div><b>{{ $session->classroom?->name ?? 'کلاس' }}</b><span>{{ $session->starts_at?->format('Y/m/d H:i') }} تا {{ $session->ends_at?->format('H:i') }}</span></div>
   @if (! $session->meeting_url && ! $session->classroom?->meeting_url)
    <span class="tag">{{ $session->meeting_provider === 'skyroom' ? 'اتصال Skyroom در انتظار فعال‌سازی' : 'لینک ورود هنوز ثبت نشده' }}</span>
   @endif
   <form method="POST" action="{{ route('student.class-sessions.join', $session) }}">@csrf<button class="btn s" type="submit">بررسی وضعیت ورود</button></form>
  </div>
 @empty
  <p>جلسه‌ی برنامه‌ریزی‌شده‌ای برای شما ثبت نشده است.</p>
 @endforelse
 {{ $sessions->links() }}
</div></section>
@endsection