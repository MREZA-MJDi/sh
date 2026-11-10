@extends('layouts.student')

@section('title', 'آزمون‌های من')

@section('content')
    <section class="card">
        <div class="chead"><div><span class="student-section-eyebrow">سنجش یادگیری</span><h2>آزمون‌های مجاز من</h2></div><span class="pill">{{ \App\Support\PersianDate::toPersianDigits($exams->total()) }} آزمون</span></div>
        <div class="student-exam-list">
            @forelse ($exams as $exam)
                @php($lastAttempt = $exam->attempts->first())
                <article class="student-exam-item">
                    <div class="student-exam-item__head">
                        <div><h3>{{ $exam->title }}</h3><span class="mu">{{ $exam->course?->title ?? $exam->classroom?->name ?? 'آزمون کلاسی' }} · {{ \App\Support\PersianDate::toPersianDigits($exam->questions_count) }} سؤال</span></div>
                        @if ($lastAttempt?->status === 'graded')<span class="tag d">نتیجه آماده</span>
                        @elseif ($lastAttempt?->status === 'pending_review')<span class="tag w">در انتظار تصحیح</span>
                        @elseif ($lastAttempt?->status === 'in_progress')<span class="tag">در حال انجام</span>
                        @else<span class="tag">منتشرشده</span>@endif
                    </div>
                    <div class="mu">شروع: {{ $exam->starts_at ? \App\Support\PersianDate::format($exam->starts_at, 'Y/m/d H:i') : 'در دسترس' }} · مدت: {{ $exam->duration_minutes }} دقیقه · دفعات مجاز: {{ $exam->attempts_allowed }}</div>
                    <a class="btn s" href="{{ route('student.exams.show', $exam) }}">مشاهده آزمون</a>
                </article>
            @empty
                <div class="empty">در حال حاضر آزمون منتشرشده‌ای برای دوره‌ها و کلاس‌های تو وجود ندارد.</div>
            @endforelse
        </div>
        @if ($exams->hasPages())<div class="frm">{{ $exams->links() }}</div>@endif
    </section>
@endsection
