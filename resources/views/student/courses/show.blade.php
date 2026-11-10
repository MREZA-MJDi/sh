@extends('layouts.student')

@section('title', $course->title)

@section('content')
    <a class="back" href="{{ route('student.courses.index') }}">← همه دوره‌های من</a>
    <section class="hero student-welcome">
        <div class="txt">
            <span class="student-welcome__eyebrow">ادامه مسیر یادگیری</span>
            <h2>{{ $course->title }}</h2>
            <p>{{ $course->description ?: 'درس‌های منتشرشدهٔ این دوره را به‌ترتیب مطالعه کن و پیشرفتت را ثبت کن.' }}</p>
            <span class="mu">مدرس: {{ $course->teacher?->name ?? 'تعیین نشده' }}</span>
        </div>
    </section>

    @if ($sections->isNotEmpty())
        <nav class="student-course-sections-nav" aria-label="بخش‌های دوره">
            @foreach ($sections as $courseSection)
                <a
                    class="btn {{ $selectedSection?->id === $courseSection->id ? '' : 'g' }} s"
                    href="{{ route('student.courses.show', ['course' => $course, 'section' => $courseSection->id, 'sections_page' => $sections->currentPage()]) }}"
                    @if ($selectedSection?->id === $courseSection->id) aria-current="page" @endif
                >{{ $courseSection->title }}</a>
            @endforeach
        </nav>
        <div class="student-course-pagination">{{ $sections->links() }}</div>
    @endif

    @if ($selectedSection)
        <section class="card student-course-section">
            <div class="chead">
                <div><span class="student-section-eyebrow">بخش دوره</span><h2>{{ $selectedSection->title }}</h2></div>
                <span class="pill">{{ number_format($lessons->total()) }} درس</span>
            </div>
            @if (filled($selectedSection->description))<p class="mu">{{ $selectedSection->description }}</p>@endif
            @forelse ($lessons as $lesson)
                @php($lessonProgress = $lesson->progress->first())
                <article class="student-course-lesson">
                    <div class="student-course-lesson__head">
                        <div><h3>{{ $lesson->title }}</h3><span class="mu">{{ $lesson->duration_minutes ? $lesson->duration_minutes.' دقیقه' : 'درس آموزشی' }}</span></div>
                        @if ($lessonProgress?->completed_at)
                            <span class="tag d">تکمیل‌شده</span>
                        @elseif (($lessonProgress?->progress_percent ?? 0) > 0)
                            <span class="tag w">{{ $lessonProgress->progress_percent }}٪ مطالعه‌شده</span>
                        @else
                            <span class="tag">شروع‌نشده</span>
                        @endif
                    </div>
                    @if (filled($lesson->body))
                        <details class="student-course-lesson__body">
                            <summary class="btn g s">مشاهده متن درس</summary>
                            <div class="student-course-lesson__text">{{ $lesson->body }}</div>
                        </details>
                    @endif
                    <form method="POST" action="{{ route('student.courses.lessons.progress', [$course, $lesson]) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="progress_percent" value="{{ $lessonProgress?->completed_at ? 0 : 100 }}">
                        <button class="btn s" type="submit">{{ $lessonProgress?->completed_at ? 'علامت‌گذاری به‌عنوان ناتمام' : 'درس را تکمیل کردم' }}</button>
                    </form>
                </article>
            @empty
                <div class="empty">هنوز درسی در این بخش منتشر نشده است.</div>
            @endforelse
            <div class="student-course-pagination">{{ $lessons->links() }}</div>
        </section>
    @elseif ($sections->isEmpty())
        <section class="card"><div class="empty">محتوای منتشرشده‌ای برای این دوره وجود ندارد.</div></section>
    @endif
@endsection
