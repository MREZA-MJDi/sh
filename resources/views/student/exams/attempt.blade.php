@extends('layouts.student')

@section('title', 'پاسخ‌گویی به آزمون')

@section('content')
    @php($exam = $attempt->exam)
    <form class="stack" method="POST" action="{{ route('student.exam-attempts.submit', $attempt) }}">
        @csrf
        <section class="card">
            <span class="student-section-eyebrow">آزمون در حال انجام</span>
            <h2>{{ $exam->title }}</h2>
            <div class="sum"><span class="tag">{{ $exam->duration_minutes }} دقیقه</span><span class="tag">{{ $exam->questions->count() }} سؤال</span></div>
            <p class="mu">پس از ثبت نهایی، امکان ویرایش پاسخ‌ها وجود ندارد. زمان ارسال در سرور بررسی می‌شود.</p>
        </section>

        @foreach ($exam->questions as $question)
            @php($saved = $attempt->answers->firstWhere('question_id', $question->id))
            <section class="card q">
                <div class="chead"><h2>{{ $loop->iteration }}. {{ $question->question }}</h2><span class="tag">{{ $question->score }} نمره</span></div>
                @if ($question->type === 'text')
                    <label for="answer-{{ $question->id }}">پاسخ تشریحی</label>
                    <textarea class="fld" id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]" rows="5" maxlength="20000">{{ old('answers.'.$question->id, $saved?->answer) }}</textarea>
                @elseif ($question->type === 'multiple')
                    @php($selected = old('answers.'.$question->id, $saved?->answer ? (json_decode($saved->answer, true) ?: []) : []))
                    @foreach ($question->options ?? [] as $option)
                        <label class="opt"><input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option }}" @checked(is_array($selected) && in_array($option, $selected, true))><span>{{ $option }}</span></label>
                    @endforeach
                @else
                    @foreach ($question->options ?? [] as $option)
                        <label class="opt"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $option }}" @checked((string) old('answers.'.$question->id, $saved?->answer) === (string) $option)><span>{{ $option }}</span></label>
                    @endforeach
                @endif
            </section>
        @endforeach

        <section class="card"><div class="frm"><span>پاسخ‌ها را مرور کن و سپس ثبت نهایی را بزن.</span><button class="btn" type="submit" onclick="return confirm('پاسخ‌ها ثبت نهایی شوند؟')">ثبت نهایی آزمون</button></div></section>
    </form>
@endsection
