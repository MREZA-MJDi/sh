@extends('public.layouts.academy')
@section('title', 'پروفایل و دوره‌های ' . $teacher->name . ($teacher->subject ? ' - مدرس ' . $teacher->subject : '') . $settings->title_separator . $settings->site_name)
@section('description', \Illuminate\Support\Str::limit(strip_tags($teacher->profile_note ?: ($teacher->name . ' مدرس ' . ($teacher->subject ?: 'دوره‌های آموزشی') . ' در آکادمی ' . $settings->site_name)), 155))
@section('canonical', route('public.teachers.show', $teacher))

@section('content')
<header class="ac-page-head">
    <div class="ac-wrap">
        <div style="font-size:13px;color:#9fb0d8">
            <a href="{{ route('home') }}">خانه</a> ‹ <a href="{{ route('public.teachers.index') }}">مدرسان</a> ‹ {{ $teacher->name }}
        </div>
        <div style="display:flex;align-items:center;gap:22px;margin-top:18px;flex-wrap:wrap">
            <div class="ac-avatar" style="width:96px;height:96px;font-size:38px;margin:0;box-shadow:0 10px 25px rgba(0,0,0,0.25)">
                {{ mb_substr($teacher->name, 0, 1) }}
            </div>
            <div>
                <h1 style="margin:0 0 6px">{{ $teacher->name }}</h1>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                    <span class="ac-pill">{{ $teacher->subject ?: 'مدرس آکادمی' }}</span>
                    <span style="color:#cbd5f1;font-size:13px">مدرس رسمی آموزشگاه</span>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="ac-stats">
    <div class="ac-wrap ac-grid ac-grid-3">
        <div class="ac-stat">
            <b>{{ number_format($stats['courses_count']) }}</b>
            <span>دوره آموزشی منتشرشده</span>
        </div>
        <div class="ac-stat">
            <b>{{ number_format($stats['lessons_count']) }}</b>
            <span>جلسه و درس آموزشی</span>
        </div>
        <div class="ac-stat">
            <b>{{ number_format($stats['enrollments_count']) }}</b>
            <span>دانشجو و ثبت‌نام در دوره‌ها</span>
        </div>
    </div>
</section>

<div class="ac-wrap ac-detail-grid">
    <div>
        <section class="ac-card ac-block">
            <h2>درباره مدرس</h2>
            @if($teacher->profile_note)
                <p style="white-space:pre-line;line-height:2;color:var(--ac-ink);font-size:15px">{{ $teacher->profile_note }}</p>
            @else
                <p class="ac-muted" style="line-height:2;font-size:14px">
                    {{ $teacher->name }} از مدرسان متخصص حوزه {{ $teacher->subject ?: 'آموزش' }} در آکادمی {{ $settings->site_name }} است. می‌توانید دوره‌ها و سرفصل‌های تدریس‌شده توسط ایشان را در ادامه مشاهده کنید.
                </p>
            @endif
        </section>

        <section class="ac-card ac-block">
            <div class="ac-section-head" style="margin-bottom:18px">
                <div>
                    <span class="ac-eyebrow">محتوای آموزشی</span>
                    <h2 style="font-size:22px">دوره‌های {{ $teacher->name }}</h2>
                </div>
                <span class="ac-pill">{{ $courses->count() }} دوره</span>
            </div>

            @if($courses->isNotEmpty())
                <div class="ac-grid" style="grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));gap:18px">
                    @foreach($courses as $course)
                        <x-public.academy.course-card :course="$course" />
                    @endforeach
                </div>
            @else
                <div class="ac-empty">
                    در حال حاضر دوره عمومی جدیدی برای این مدرس ثبت نشده است.
                </div>
            @endif
        </section>
    </div>

    <aside class="ac-card ac-sticky">
        <div style="text-align:center;padding:10px 0 16px;border-bottom:1px solid var(--ac-line)">
            <div class="ac-avatar" style="width:78px;height:78px;font-size:28px">{{ mb_substr($teacher->name, 0, 1) }}</div>
            <h3 style="margin:8px 0 4px;font-size:18px">{{ $teacher->name }}</h3>
            <span class="ac-pill">{{ $teacher->subject ?: 'مدرس' }}</span>
        </div>

        <ul class="ac-list" style="margin-top:14px">
            <li>
                <span>حوزه تدریس</span>
                <b>{{ $teacher->subject ?: 'عمومی' }}</b>
            </li>
            <li>
                <span>دوره‌های فعال</span>
                <b>{{ $stats['courses_count'] }}</b>
            </li>
            <li>
                <span>مجموع درس‌ها</span>
                <b>{{ $stats['lessons_count'] }}</b>
            </li>
            <li>
                <span>دانشجویان ثبت‌نامی</span>
                <b>{{ number_format($stats['enrollments_count']) }}</b>
            </li>
        </ul>

        <div style="margin-top:20px;display:flex;flex-direction:column;gap:8px">
            <a class="ac-btn" href="{{ route('public.contact') }}" style="width:100%">
                ارتباط یا مشاوره
            </a>
            @if($teacher->subject)
                <a class="ac-btn ac-btn-outline" href="{{ route('public.courses.index', ['subject' => $teacher->subject]) }}" style="width:100%">
                    دوره‌های رشته {{ $teacher->subject }}
                </a>
            @endif
            <a class="ac-link" href="{{ route('public.teachers.index') }}" style="display:block;text-align:center;margin-top:6px;font-size:13px">
                ← بازگشت به فهرست مدرسان
            </a>
        </div>
    </aside>
</div>
@endsection
