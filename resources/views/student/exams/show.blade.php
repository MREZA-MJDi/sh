@extends('layouts.student')

@section('title', $exam->title)

@section('content')
    <a class="back" href="{{ route('student.exams.index') }}">← آزمون‌های من</a>
    <section class="card">
        <span class="student-section-eyebrow">آزمون داخلی</span>
        <h2>{{ $exam->title }}</h2>
        <p class="mu">{{ $exam->course?->title ?? $exam->classroom?->name ?? 'آزمون کلاسی' }}</p>
        @if (filled($exam->description))<p class="student-course-lesson__text">{{ $exam->description }}</p>@endif
        <div class="sum">
            <span class="tag">{{ $exam->questions->count() }} سؤال</span>
            <span class="tag">{{ $exam->duration_minutes }} دقیقه</span>
            <span class="tag">{{ $exam->attempts_allowed }} بار مجاز</span>
        </div>
        @php($lastAttempt = $exam->attempts->first())
        @if ($lastAttempt && $lastAttempt->status !== 'in_progress')
            <div class="info">
                {{ $lastAttempt->status === 'pending_review' ? 'پاسخ‌ها ثبت شده و در انتظار تصحیح سؤال‌های تشریحی است.' : 'آخرین نتیجه: '.($lastAttempt->score ?? '—').' از '.($lastAttempt->max_score ?? '—') }}
            </div>
            <a class="btn s" href="{{ route('student.exams.result', $exam) }}">مشاهده نتیجه و تلاش‌ها</a>
        @elseif ($lastAttempt && $lastAttempt->status === 'in_progress')
            <a class="btn" href="{{ route('student.exams.attempt', [$exam, $lastAttempt]) }}">ادامه آزمون</a>
        @else
            <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                @csrf
                <button class="btn" type="submit">شروع آزمون</button>
            </form>
        @endif
    </section>
@endsection
