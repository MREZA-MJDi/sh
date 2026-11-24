@extends('student.layouts.app')
@section('title', 'نتیجه آزمون')
@section('content')
<section class="page"><x-portal.page-head :title="$exam->title" icon="chart" tone="tracking" :back="route('student.exams.index')" /><div class="card"><h2>نتایج تلاش‌ها</h2>
 @forelse ($attempts as $attempt)
  <div class="row"><div>تلاش شماره {{ $attempt->attempt_number }}<small>{{ $attempt->submitted_at ? \App\Support\PersianDate::format($attempt->submitted_at,'Y/m/d H:i') : '—' }}</small><x-portal.status domain="exam" :value="$attempt->status"/>
   @if ($attempt->feedback)<small>{{ $attempt->feedback }}</small>@endif
  </div><i class="bd {{ $attempt->status === 'graded' ? 'gn' : 'am' }}">{{ $attempt->score === null ? 'در انتظار بررسی' : $attempt->score.' / '.$attempt->max_score }}</i></div>
 @empty
  <x-portal.empty-state title="تلاشی برای این آزمون ثبت نشده است." icon="chart" tone="tracking"/>
 @endforelse
 <a class="pill" href="{{ route('student.exams.index') }}">بازگشت به آزمون‌ها</a>
</div></section>
@endsection