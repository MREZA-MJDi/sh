@extends('student.layouts.app')
@section('title', 'آزمون آنلاین')
@section('hide-topbar-title', '1')
@section('content')
<section class="page" id="exam-take">
<x-portal.page-head icon="check" tone="tracking" :title="$exam->title"/>

 <form class="card q" id="exam" method="POST" action="{{ route('student.exam-attempts.submit', $attempt) }}" data-seconds="{{ $remainingSeconds ?? '' }}">
  @csrf
  <div class="chead"><h2>{{ $questions->count() }} سؤال</h2>
   @if ($remainingSeconds !== null)<span class="tag w">زمان باقی‌مانده: <b id="examTimer" dir="ltr">--:--</b></span>@endif
  </div>
  @if(filled($exam->description))<p>{{ $exam->description }}</p>@endif
  <p>به سؤال‌ها جواب بده و در پایان روی «ارسال پاسخ‌ها» بزن.</p>
  @foreach ($questions as $question)
   @php
    $saved = $answers->get($question->id)?->answer;
    $savedValues = is_string($saved) ? (json_decode($saved, true) ?: [$saved]) : (array) $saved;
    $options = $question->options ?? [];
   @endphp
   <section class="q-item">
    <h3>{{ $loop->iteration }}. {{ $question->question }} <small>({{ $question->score }} نمره)</small></h3>
    @if ($question->type === 'text')
     <textarea aria-label="پاسخ سؤال {{ $loop->iteration }}" name="answers[{{ $question->id }}]" rows="4" placeholder="پاسخ تشریحی">{{ $saved }}</textarea>
    @elseif ($question->type === 'multiple')
     @foreach ($options as $key => $option)
      <label><input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $key }}" @checked(in_array((string) $key, array_map('strval', $savedValues), true))> {{ $option }}</label>
     @endforeach
    @else
     @foreach ($options as $key => $option)
      <label><input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}" @checked(in_array((string) $key, array_map('strval', $savedValues), true))> {{ $option }}</label>
     @endforeach
    @endif
   </section>
  @endforeach
  <button class="btn s" type="submit" data-confirm="آزمون تمام شود و پاسخ‌ها ارسال شوند؟">ارسال پاسخ‌ها</button>
 </form>
</section>
@endsection
