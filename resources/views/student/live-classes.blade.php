@extends('student.layouts.app')
@section('title', 'کلاس‌های من')
@section('content')
<section class="page" id="live-classes"><div class="card"><div class="chead"><h2>جلسات پیش‌رو</h2><span class="tag">{{ ($sessions ?? collect())->count() }} جلسه</span></div>
 @forelse (($sessions ?? collect()) as $session)
  <div class="trk"><div class="th"></div><div><b>{{ $session->classroom?->name ?? 'کلاس' }}</b><span>{{ $session->starts_at?->format('Y/m/d H:i') }} تا {{ $session->ends_at?->format('H:i') }}</span></div>
   <form method="POST" action="{{ route('student.class-sessions.join', $session) }}">@csrf<button class="btn s" type="submit">ورود به کلاس</button></form>
  </div>
 @empty
  <p>جلسه‌ی برنامه‌ریزی‌شده‌ای برای شما ثبت نشده است.</p>
 @endforelse
</div></section>
@endsection