@extends('layouts.student')

@section('title', 'دوره‌های من')

@section('content')
    <section class="card">
        <div class="chead">
            <div><span class="student-section-eyebrow">مسیر یادگیری</span><h2>دوره‌های ثبت‌نام‌شده من</h2></div>
            <span class="pill">{{ \App\Support\PersianDate::toPersianDigits($courses->total()) }} دوره</span>
        </div>
        <div class="student-course-list">
            @forelse ($courses as $course)
                @php($courseProgress = $progressByCourse->get($course->id))
                <article class="student-course-item">
                    <div class="student-course-item__head">
                        <div><h3>{{ $course->title }}</h3><span class="mu">{{ $course->subject ?: 'دوره آموزشی' }} · مدرس: {{ $course->teacher?->name ?? 'تعیین نشده' }}</span></div>
                        <strong>{{ \App\Support\PersianDate::toPersianDigits((string) ($courseProgress->progress_percent ?? 0)) }}٪</strong>
                    </div>
                    <div class="student-attendance-meter__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $courseProgress->progress_percent ?? 0 }}">
                        <span style="width: {{ max(0, min(100, (int) ($courseProgress->progress_percent ?? 0))) }}%"></span>
                    </div>
                    <p class="mu">{{ \App\Support\PersianDate::toPersianDigits((string) ($courseProgress->completed_lessons ?? 0)) }} از {{ \App\Support\PersianDate::toPersianDigits((string) ($courseProgress->total_lessons ?? 0)) }} درس تکمیل شده</p>
                    <a class="btn s" href="{{ route('student.courses.show', $course) }}">ادامه یادگیری</a>
                </article>
            @empty
                <div class="empty">دورهٔ فعالی برای این حساب ثبت نشده است.</div>
            @endforelse
        </div>
        @if ($courses->hasPages())<div class="frm">{{ $courses->links() }}</div>@endif
    </section>
@endsection
