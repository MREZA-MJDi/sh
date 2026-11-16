@extends('student.layouts.app')

@section('title', 'آزمون آنلاین')

@section('content')
@php
    $current = $current ?? (int) request('q', 1);   // شماره‌ی سؤال فعلی
    $total   = $total ?? 10;                        // تعداد کل سؤال‌ها
    $answered = $answered ?? [];                    // شماره‌ی سؤال‌های پاسخ‌داده‌شده
@endphp
<section class="page" id="exam-take">
    <form class="card q" id="exam" method="POST" action="{{ url('student/exams/submit') }}"
          data-seconds="{{ $remainingSeconds ?? 3600 }}">
        @csrf
        <input type="hidden" name="question" value="{{ $current }}">

        <div class="chead">
            <h2>{{ $exam->title ?? 'آزمون' }}</h2>
            <span class="tag w">زمان باقی‌مانده: <b id="examTimer" dir="ltr">--:--</b></span>
        </div>

        <div class="prog" style="margin-bottom:16px"><i style="width:{{ round($current / max($total, 1) * 100) }}%"></i></div>

        <p style="font-size:16px;font-weight:600;margin-bottom:14px">سؤال {{ $current }} از {{ $total }}: {{ $question->text ?? '' }}</p>

        @foreach (($question->options ?? [1 => '', 2 => '', 3 => '', 4 => '']) as $key => $text)
            <label><input type="radio" name="answer" value="{{ $key }}" @checked(($question->answer ?? null) == $key)> {{ $text }}</label>
        @endforeach

        <div class="frm">
            <div class="qn">
                @for ($i = 1; $i <= $total; $i++)
                    <button type="submit" name="go" value="{{ $i }}" class="{{ $i == $current ? 'on' : (in_array($i, $answered) ? 'done' : '') }}">{{ $i }}</button>
                @endfor
            </div>
            <span style="flex:1"></span>
            <button type="submit" name="go" value="prev" class="pill" @disabled($current <= 1)>قبلی</button>
            <button type="submit" name="go" value="next" class="btn s" @disabled($current >= $total)>بعدی</button>
            <button type="submit" name="go" value="finish" class="btn s" data-confirm="آزمون پایان یابد؟">پایان آزمون</button>
        </div>
    </form>
</section>
@endsection
