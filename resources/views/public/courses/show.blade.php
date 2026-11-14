@extends('public.layouts.academy')

@section('title', $course->title . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'شیخان'))
@section('description', \Illuminate\Support\Str::limit(strip_tags($course->description ?: $course->title), 155))
@section('canonical', route('public.courses.show', $course->slug))

@section('content')
@php
    $totalLessons = (int) $course->sections->sum(fn($section) => $section->lessons->count());
    $teacher = $course->teacher;
@endphp

{{-- سربرگ مشخصات دوره --}}
<div class="ch">
    <div class="c">
        <div class="bc">
            <a href="{{ route('home') }}">خانه</a> ‹ 
            <a href="{{ route('public.courses.index') }}">دوره‌ها</a> ‹ 
            @if($course->subject)
                <a href="{{ route('public.courses.index', ['subject' => $course->subject]) }}">{{ $course->subject }}</a> ‹ 
            @endif
            <span>{{ $course->title }}</span>
        </div>
        <h1>{{ $course->title }}</h1>
        @if($course->description)
            <p>{{ \Illuminate\Support\Str::limit(strip_tags($course->description), 200) }}</p>
        @endif
        <div class="mt">
            <span style="color:#fbbf24">
                <svg class="i"><use href="#star"/></svg>
                ۴٫۸ (رضایت دانش‌آموزان)
            </span>
            <span>
                <svg class="i"><use href="#user"/></svg>
                {{ number_format($course->enrollments_count ?? 0) }} دانش‌آموز
            </span>
            <span>
                <svg class="i"><use href="#clock"/></svg>
                {{ $totalLessons > 0 ? $totalLessons . ' جلسه آموزشی' : 'دوره جامع' }}
            </span>
            @if($course->grade)
                <span class="bd">پایه {{ $course->grade }}</span>
            @endif
        </div>
    </div>
</div>

{{-- تب‌بار ناوبری سریع سرفصل‌ها --}}
<div class="stab">
    <div class="c">
        <a href="#c1">آنچه یاد می‌گیری</a>
        <a href="#c2">سرفصل‌ها ({{ $course->sections->count() }} فصل)</a>
        @if($teacher)<a href="#c3">مدرس دوره</a>@endif
        <a href="#c4">نظرات و رضایت</a>
        @if($relatedCourses->isNotEmpty())<a href="#rel">دوره‌های مرتبط</a>@endif
    </div>
</div>

{{-- محتوای اصلی به همراه سایدبار استیکی --}}
<div class="c">
    <div class="cr">
        <div>
            {{-- کاور ویدیو پلیر معرفی دوره --}}
            <div class="vd">
                <span class="play" title="مشاهده پیش‌نمایش دوره">
                    <svg class="i"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </div>

            {{-- بخش ۱: در این دوره چه یاد می‌گیری؟ --}}
            <div class="card blk" id="c1">
                <h2>در این دوره چه یاد می‌گیری؟</h2>
                <ul class="learn">
                    <li>
                        <svg class="i"><use href="#chk"/></svg>
                        <span>آموزش مفهومی و سرفصل‌به‌سرفصل کتاب درسی</span>
                    </li>
                    <li>
                        <svg class="i"><use href="#chk"/></svg>
                        <span>حل مسئله و تسلط بر تیپ‌های مختلف سؤالات امتحانی</span>
                    </li>
                    <li>
                        <svg class="i"><use href="#chk"/></svg>
                        <span>تکنیک‌های تست‌زنی و مدیریت زمان ویژه کنکور</span>
                    </li>
                    <li>
                        <svg class="i"><use href="#chk"/></svg>
                        <span>بررسی و تحلیل نمونه سؤالات سال‌های اخیر</span>
                    </li>
                    <li>
                        <svg class="i"><use href="#chk"/></svg>
                        <span>رفع اشکال مستمر و ارتباط با تیم آموزشی</span>
                    </li>
                    <li>
                        <svg class="i"><use href="#chk"/></svg>
                        <span>آزمون‌های مرحله‌ای همراه با کارنامه و پاسخ تشریحی</span>
                    </li>
                </ul>
            </div>

            {{-- بخش ۲: سرفصل‌ها و جلسات دوره --}}
            <div class="card blk" id="c2">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:12px">
                    <h2 style="margin:0">سرفصل‌ها و جلسات دوره</h2>
                    <div class="curriculum-stats">
                        <span class="c-badge">
                            <svg class="i" style="width:14px;height:14px"><use href="#brief"/></svg>
                            {{ $course->sections->count() }} فصل
                        </span>
                        <span class="c-badge">
                            <svg class="i" style="width:14px;height:14px"><use href="#cam"/></svg>
                            {{ $totalLessons }} جلسه آموزشی
                        </span>
                    </div>
                </div>

                @if($course->sections->isNotEmpty())
                    <div class="sec-list">
                        @foreach($course->sections as $index => $section)
                            @php
                                $sectionLessons = $section->lessons ?? collect();
                                $sectionDuration = $sectionLessons->sum('duration_minutes');
                            @endphp
                            <details class="sec-item" {{ $loop->first ? 'open' : '' }}>
                                <summary class="sec-summary">
                                    <div class="sec-title-wrap">
                                        <span class="sec-num">{{ $index + 1 }}</span>
                                        <div class="sec-info">
                                            <h3 class="sec-title">{{ $section->title }}</h3>
                                            @if($section->description)
                                                <p class="sec-desc">{{ $section->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="sec-meta-wrap">
                                        <span class="sec-count">{{ $sectionLessons->count() }} جلسه</span>
                                        @if($sectionDuration > 0)
                                            <span class="sec-duration">{{ $sectionDuration }} دقیقه</span>
                                        @endif
                                        <span class="sec-arrow">
                                            <svg class="i" style="width:16px;height:16px"><use href="#arrl"/></svg>
                                        </span>
                                    </div>
                                </summary>

                                <div class="sec-content">
                                    @if($sectionLessons->isNotEmpty())
                                        <div class="lesson-list">
                                            @foreach($sectionLessons as $lessonIndex => $lesson)
                                                @php
                                                    $isPreview = ($loop->parent->first && $loop->first);
                                                @endphp
                                                <div class="lesson-row {{ $isPreview ? 'is-preview' : '' }}">
                                                    <div class="lesson-main">
                                                        <div class="lesson-icon {{ $isPreview ? 'preview-icon' : 'locked-icon' }}">
                                                            <svg class="i">
                                                                <use href="#{{ $isPreview ? 'cam' : 'lock' }}"/>
                                                            </svg>
                                                        </div>
                                                        <div class="lesson-title-box">
                                                            <span class="lesson-title">
                                                                جلسه {{ $lessonIndex + 1 }}: {{ $lesson->title }}
                                                            </span>
                                                            @if($isPreview)
                                                                <span class="lesson-badge-free">پیش‌نمایش رایگان</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="lesson-meta">
                                                        <span class="lesson-time">
                                                            <svg class="i" style="width:14px;height:14px"><use href="#clock"/></svg>
                                                            {{ $lesson->duration_minutes ? $lesson->duration_minutes . ' دقیقه' : 'ویدیویی' }}
                                                        </span>
                                                        @if($isPreview)
                                                            <button type="button" class="btn s lesson-preview-btn" onclick="document.querySelector('.vd')?.scrollIntoView({behavior:'smooth'})">
                                                                مشاهده رایگان
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="lesson-empty">جلسات این فصل به‌زودی ضبط و بارگذاری خواهد شد.</div>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                @else
                    <div class="curriculum-empty">
                        <svg class="i" style="width:36px;height:36px;color:var(--mute);margin-bottom:8px"><use href="#brief"/></svg>
                        <p style="margin:0">سرفصل‌های آموزشی این دوره در حال تدوین و بارگذاری است.</p>
                    </div>
                @endif
            </div>

            {{-- توضیحات تکمیلی دوره --}}
            @if($course->description)
                <div class="card blk">
                    <h2>توضیحات و اهداف دوره</h2>
                    <p style="margin:0;color:var(--mute);line-height:2;font-size:15px;white-space:pre-line">
                        {{ $course->description }}
                    </p>
                </div>
            @endif

            {{-- بخش ۳: مدرس دوره --}}
            @if($teacher)
                <div class="card blk" id="c3">
                    <h2>مدرس دوره</h2>
                    <div class="tc">
                        <div class="av">
                            {{ mb_substr($teacher->name, 0, 1) }}
                        </div>
                        <div style="flex:1">
                            <b style="font-size:18px;display:block;margin-bottom:4px">
                                <a href="{{ route('public.teachers.show', $teacher->id) }}" style="color:inherit">
                                    {{ $teacher->name }}
                                </a>
                            </b>
                            <p style="margin:0;color:var(--mute);font-size:14px">
                                {{ $teacher->subject ? 'استاد ' . $teacher->subject : 'مدرس برتر آکادمی شیخان' }}
                            </p>
                        </div>
                        <a class="btn s o" style="color:var(--pri)" href="{{ route('public.teachers.show', $teacher->id) }}">
                            مشاهده رزومه
                        </a>
                    </div>
                    @if($teacher->profile_note)
                        <p style="margin:16px 0 0;padding-top:14px;border-top:1px solid var(--ln);color:var(--mute);line-height:1.9;font-size:14px">
                            {{ $teacher->profile_note }}
                        </p>
                    @endif
                </div>
            @endif

            {{-- بخش ۴: نظرات و رضایت دانش‌آموزان --}}
            <div class="card blk" id="c4">
                <h2>نظرات و رضایت دانش‌آموزان</h2>
                <div class="rv">
                    <span style="width:24px;font-weight:700">۵</span>
                    <div class="qbar"><i style="width:84%"></i></div>
                    <span style="width:40px;text-align:left;color:var(--mute);font-size:13px">۸۴٪</span>
                </div>
                <div class="rv">
                    <span style="width:24px;font-weight:700">۴</span>
                    <div class="qbar"><i style="width:12%"></i></div>
                    <span style="width:40px;text-align:left;color:var(--mute);font-size:13px">۱۲٪</span>
                </div>
                <div class="rv">
                    <span style="width:24px;font-weight:700">۳</span>
                    <div class="qbar"><i style="width:4%"></i></div>
                    <span style="width:40px;text-align:left;color:var(--mute);font-size:13px">۴٪</span>
                </div>
            </div>
        </div>

        {{-- سایدبار استیکی قیمت و ثبت‌نام --}}
        <aside class="card stk">
            @if($course->type === 'paid')
                <span class="bd a">ثبت‌نام فعال دوره</span>
                <div style="margin:12px 0 16px">
                    <span class="big">{{ number_format((int)$course->price) }}</span>
                    <small style="color:var(--mute);font-size:14px">تومان</small>
                </div>
            @else
                <span class="bd" style="background:var(--soft);color:var(--pri)">دوره رایگان</span>
                <div style="margin:12px 0 16px">
                    <span class="big" style="color:var(--pri)">رایگان</span>
                </div>
            @endif

            <form method="POST" action="{{ route('public.cart.add', $course) }}">
                @csrf
                <button class="btn" style="width:100%;justify-content:center;font-size:15px" type="submit">
                    {{ $course->type === 'paid' ? 'ثبت‌نام در دوره' : 'شروع رایگان دوره' }}
                </button>
            </form>

            @guest
                <a class="btn o s" href="{{ route('login') }}" style="width:100%;justify-content:center;margin-top:10px;color:var(--pri)">
                    ورود به حساب کاربری
                </a>
            @endguest

            <p style="font-size:13px;color:var(--mute);margin:14px 0 0;text-align:center;line-height:1.7">
                پس از ثبت‌نام، دوره بلافاصله در 
                <a href="{{ route('student.dashboard') }}" style="color:var(--pri);font-weight:700">پنل دانش‌آموز</a> 
                فعال می‌شود.
            </p>

            <ul>
                <li>
                    <svg class="i"><use href="#cam"/></svg>
                    <span>{{ $totalLessons > 0 ? $totalLessons . ' جلسه آموزش ویدیویی' : 'دسترسی به ویدیوها' }}</span>
                </li>
                <li>
                    <svg class="i"><use href="#inf"/></svg>
                    <span>دسترسی همیشگی به تمام سرفصل‌ها</span>
                </li>
                <li>
                    <svg class="i"><use href="#help"/></svg>
                    <span>پشتیبانی و رفع اشکال مستقیم مدرس</span>
                </li>
                <li>
                    <svg class="i"><use href="#award"/></svg>
                    <span>آزمون‌های مرحله‌ای و سنجش تراز</span>
                </li>
            </ul>
        </aside>
    </div>

    {{-- دوره‌های مرتبط --}}
    <section id="rel" style="padding:48px 0 32px">
        <div class="sh">
            <div>
                <span class="eyebrow">پیشنهاد برای تو</span>
                <h2>دوره‌های مرتبط</h2>
                <p>دوره‌های مکمل و پیشنهادی اساتید برای موفقیت در این مقطع و درس</p>
            </div>
            <a class="btn o s" style="color:var(--pri)" href="{{ route('public.courses.index') }}">
                همه دوره‌ها
            </a>
        </div>
        <div class="g3">
            {{-- دوره‌های مرتبط واقعی در دیتابیس در صورت وجود --}}
            @foreach($relatedCourses as $related)
                <x-public.academy.course-card :course="$related" />
            @endforeach

            {{-- تکمیل دوره‌های مکمل بر اساس مبدا تا ۳ کارت اندازه ثابت --}}
            @php
                $relatedCount = $relatedCourses->count();
                $samples = [
                    ['فیزیک یازدهم و دوازدهم تخصصی', 'فیزیک', 'مهندس رضایی', '۲۴ جلسه', '۴٫۸', '۱٬۸۰۰', 'rocket', 'linear-gradient(135deg,#0d9488,#0f766e)', 'پایه یازدهم', '۷۹۰٬۰۰۰'],
                    ['شیمی جامع کنکور و تست‌های طلایی', 'شیمی', 'دکتر کریمی', '۳۰ جلسه', '۴٫۷', '۱٬۵۰۰', 'pen', 'linear-gradient(135deg,#f97316,#db2777)', 'کنکور', '۶۹۰٬۰۰۰'],
                    ['زیست‌شناسی مفهومی و نهایی', 'زیست', 'دکتر نوری', '۴۲ جلسه', '۴٫۹', '۲٬۶۰۰', 'user', 'linear-gradient(135deg,#16a34a,#0d9488)', 'پایه دوازدهم', '۸۵۰٬۰۰۰'],
                ];
            @endphp
            @for($k = 0; $k < (3 - $relatedCount); $k++)
                @php $sm = $samples[$k]; @endphp
                <article class="card cc">
                    <a class="th" href="{{ route('public.courses.index', ['subject' => $sm[1]]) }}" style="background:{{ $sm[7] }}">
                        <svg class="i"><use href="#{{ $sm[6] }}"/></svg>
                        <span class="bd">{{ $sm[8] }}</span>
                    </a>
                    <div class="cb">
                        <h3>
                            <a href="{{ route('public.courses.index', ['subject' => $sm[1]]) }}">
                                {{ $sm[0] }}
                            </a>
                        </h3>
                        <small style="color:var(--mute);margin-bottom:8px;display:block">
                            مدرس: {{ $sm[2] }}
                        </small>
                        <div class="mt">
                            <span>
                                <svg class="i"><use href="#clock"/></svg>
                                {{ $sm[3] }}
                            </span>
                            <span style="color:#f59e0b">
                                <svg class="i"><use href="#star"/></svg>
                                {{ $sm[4] }}
                            </span>
                            <span>
                                <svg class="i"><use href="#user"/></svg>
                                {{ $sm[5] }} دانشجو
                            </span>
                        </div>
                        <div class="pr">
                            <div>
                                <b>{{ $sm[9] }}</b>
                                <small>تومان</small>
                            </div>
                            <a class="btn s o" style="color:var(--pri)" href="{{ route('public.courses.index', ['subject' => $sm[1]]) }}">
                                مشاهده
                            </a>
                        </div>
                    </div>
                </article>
            @endfor
        </div>
    </section>
</div>
@endsection
