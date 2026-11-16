@extends('student.layouts.app')
@section('title', 'نتیجه آزمون')
@section('content')
<section class="page"><div class="card"><h2>{{ $exam->title }} — نتایج تلاش‌ها</h2>
 @forelse ($attempts as $attempt)
  <div class="row"><div>تلاش شماره {{ $attempt->attempt_number }}<small>{{ $attempt->submitted_at?->format('Y/m/d H:i') }} · {{ $attempt->status }}</small>
   @if ($attempt->feedback)<small>{{ $attempt->feedback }}</small>@endif
  </div><i class="bd {{ $attempt->status === 'graded' ? 'gn' : 'am' }}">{{ $attempt->score === null ? 'در انتظار بررسی' : $attempt->score.' / '.$attempt->max_score }}</i></div>
 @empty
  <p>تلاشی برای این آزمون ثبت نشده است.</p>
 @endforelse
 <a class="pill" href="{{ route('student.exams.index') }}">بازگشت به آزمون‌ها</a>
</div></section>
@endsection