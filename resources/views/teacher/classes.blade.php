@extends('teacher.layouts.app')
@section('title', 'کلاس‌ها')
@section('content')
<h2>کلاس‌های من</h2>
<div class="g g2">
 @forelse ($classrooms as $classroom)
  <section class="c">
   <h3>{{ $classroom->name }}</h3>
   <p>{{ $classroom->course?->title ?? 'بدون دوره مرتبط' }} · {{ $classroom->students_count }} دانش‌آموز</p>
   <div class="row"><div>وضعیت<small>{{ $classroom->status }}</small></div></div>
   <h3>جلسات پیش‌رو</h3>
   @forelse ($classroom->sessions as $session)
    <div class="row"><div>{{ $session->title ?: $classroom->name }}<small>{{ $session->starts_at?->format('Y/m/d H:i') }} تا {{ $session->ends_at?->format('H:i') }}</small></div>
     @if ($session->meeting_url)<a class="b s" href="{{ $session->meeting_url }}" target="_blank" rel="noopener">ورود به جلسه</a>@else<i class="bd am">لینک ثبت نشده</i>@endif
    </div>
   @empty
    <p>جلسه‌ی آینده‌ای ثبت نشده است.</p>
   @endforelse
  </section>
 @empty
  <p>کلاسی به حساب شما اختصاص داده نشده است.</p>
 @endforelse
</div>
{{ $classrooms->links() }}
@endsection