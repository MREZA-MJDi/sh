@extends('layouts.student')

@section('title', 'نتیجه آزمون')

@section('content')
    <a class="back" href="{{ route('student.exams.index') }}">← آزمون‌های من</a>
    <section class="card">
        <h2>{{ $exam->title }}</h2>
        <p class="mu">{{ $exam->course?->title ?? $exam->classroom?->name ?? 'آزمون کلاسی' }}</p>
        @forelse ($attempts as $attempt)
            <article class="student-exam-item">
                <div class="student-exam-item__head">
                    <strong>تلاش {{ $attempt->attempt_number }}</strong>
                    @if ($attempt->status === 'graded')<span class="tag d">تصحیح‌شده</span>
                    @elseif ($attempt->status === 'pending_review')<span class="tag w">در انتظار تصحیح تشریحی</span>
                    @else<span class="tag">ثبت‌شده</span>@endif
                </div>
                <div class="student-exam-score"><strong>{{ $attempt->score === null ? 'در انتظار نمره' : $attempt->score.' از '.$attempt->max_score }}</strong></div>
                @if ($attempt->reviewed_at)<p class="mu">تصحیح توسط {{ $attempt->reviewer?->name ?? 'مدرس' }} · {{ \App\Support\PersianDate::format($attempt->reviewed_at, 'Y/m/d H:i') }}</p>@endif
                @if (filled($attempt->feedback))<p class="student-homework-item__answer">{{ $attempt->feedback }}</p>@endif
            </article>
        @empty
            <div class="empty">هنوز تلاشی برای این آزمون ثبت نشده است.</div>
        @endforelse
    </section>
@endsection
