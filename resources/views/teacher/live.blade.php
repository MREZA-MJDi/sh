@extends('teacher.layouts.app')
@section('title', 'کلاس آنلاین')
@section('content')
<h2>جلسات آنلاین پیش‌رو</h2>
<div class="g">
 @forelse ($sessions as $session)
  <section class="c"><h3>{{ $session->title ?: $session->classroom?->name }}</h3>
   <p>{{ $session->classroom?->name }} · {{ $session->starts_at?->format('Y/m/d H:i') }} تا {{ $session->ends_at?->format('H:i') }}</p>
   @if ($session->meeting_url)<a class="b" href="{{ $session->meeting_url }}" target="_blank" rel="noopener">ورود به کلاس</a>
   @elseif ($session->classroom?->meeting_url)<a class="b" href="{{ $session->classroom->meeting_url }}" target="_blank" rel="noopener">ورود به کلاس</a>
   @else<i class="bd am">لینک جلسه هنوز ثبت نشده است</i>@endif
  </section>
 @empty
  <section class="c"><p>جلسه‌ی آنلاینی برای آینده ثبت نشده است.</p></section>
 @endforelse
 {{ $sessions->links() }}
</div>
@endsection