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
        @if($course->description)<a href="#c1">معرفی دوره</a>@endif
        <a href="#c2">سرفصل‌ها ({{ $course->sections->count() }} فصل)</a>
        @if($teacher)<a href="#c3">مدرس دوره</a>@endif
        @if($relatedCourses->isNotEmpty())<a href="#rel">دوره‌های مرتبط</a>@endif
    </div>
</div>

{{-- محتوای اصلی به همراه سایدبار استیکی --}}
<div class="c">
    <div class="cr">
        <div>
            {{-- تیزر عمومی دوره؛ فقط خروجی پردازش‌شده منتشر می‌شود --}}
            @if ($course->teaser_status === 'ready' && $course->teaser_url)
                <video class="vd" style="display:block;width:100%;aspect-ratio:16/9;object-fit:cover" controls playsinline preload="none" @if($course->teaser_poster_url) poster="{{ $course->teaser_poster_url }}" @endif aria-label="تیزر دوره {{ $course->title }}">
                    <source src="{{ $course->teaser_url }}" type="video/mp4">
                    مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                </video>
            @else
                <div class="vd" role="status">
                    <span class="play"><svg class="i"><use href="#video"/></svg></span>
                    <span>
                        @if($course->teaser_status === 'uploading')
                            تیزر در حال بارگذاری است
                        @elseif($course->teaser_status === 'processing')
                            تیزر در حال آماده‌سازی است
                        @elseif($course->teaser_status === 'failed')
                            تیزر فعلاً در دسترس نیست
                        @else
                            برای این دوره تیزر منتشر نشده است
                        @endif
                    </span>
                </div>
            @endif

            {{-- معرفی دوره بر اساس محتوای ثبت‌شده در پنل مدیریت --}}
            @if($course->description)
                <div class="card blk" id="c1">
                    <h2>معرفی دوره</h2>
                    <p style="margin:0;color:var(--mute);line-height:2;font-size:15px;white-space:pre-line">{{ $course->description }}</p>
                </div>
            @endif


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
                                                    $isUnlocked = (bool) $hasCourseAccess;
                                                @endphp
                                                <div class="lesson-row {{ $isUnlocked ? 'is-unlocked' : 'is-locked' }}">
                                                    <div class="lesson-main">
                                                        <div class="lesson-icon {{ $isUnlocked ? 'unlocked-icon' : 'locked-icon' }}">
                                                            <svg class="i">
                                                                <use href="#{{ $isUnlocked ? 'play' : 'lock' }}"/>
                                                            </svg>
                                                        </div>
                                                        <div class="lesson-title-box">
                                                            <span class="lesson-title">
                                                                جلسه {{ $lessonIndex + 1 }}: {{ $lesson->title }}
                                                            </span>
                                                            @if($isUnlocked)
                                                                <span class="lesson-badge-free">محتوای دوره در دسترس است</span>
                                                            @else
                                                                <span class="lesson-badge-locked">قفل‌شده</span>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="lesson-meta">
                                                        <span class="lesson-time">
                                                            <svg class="i" style="width:14px;height:14px"><use href="#clock"/></svg>
                                                            {{ $lesson->duration_minutes ? $lesson->duration_minutes . ' دقیقه' : 'ویدیویی' }}
                                                        </span>
                                                        @if($isUnlocked)
                                                            <a class="btn s" href="{{ route('student.courses.show', $course) }}">ورود به دوره</a>
                                                        @else
                                                            <span class="lesson-lock-label">پس از ثبت‌نام فعال می‌شود</span>
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

        </div>

        {{-- سایدبار استیکی قیمت و ثبت‌نام --}}
        <aside class="card stk">
            @if($course->type === 'paid')
                <span class="bd a">{{ $hasCourseAccess ? 'دسترسی فعال' : 'دوره پولی' }}</span>
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

            @if ($hasCourseAccess)
                <a class="btn" style="width:100%;justify-content:center;font-size:15px" href="{{ route('student.courses.show', $course) }}">ورود به دوره</a>
            @elseif (auth()->check() && auth()->user()->hasRole('student') && $course->type === 'free')
                <form method="POST" action="{{ route('student.courses.enroll-free', $course) }}">
                    @csrf
                    <button class="btn" style="width:100%;justify-content:center;font-size:15px" type="submit">ثبت‌نام رایگان</button>
                </form>
            @elseif ($course->type === 'paid')
                <form method="POST" action="{{ route('public.cart.add', $course) }}">
                    @csrf
                    <button class="btn" style="width:100%;justify-content:center;font-size:15px" type="submit">ثبت‌نام در دوره</button>
                </form>
                @guest
                    <a class="btn o s" href="{{ route('login') }}" style="width:100%;justify-content:center;margin-top:10px;color:var(--pri)">ورود به حساب کاربری</a>
                @endguest
            @elseif (auth()->guest())
                <a class="btn o s" href="{{ route('login') }}" style="width:100%;justify-content:center;margin-top:10px;color:var(--pri)">برای ثبت‌نام رایگان وارد شوید</a>
            @else
                <p class="mu" style="text-align:center">ثبت‌نام رایگان فقط از حساب فعال دانش‌آموز انجام می‌شود.</p>
            @endif

            <p style="font-size:13px;color:var(--mute);margin:14px 0 0;text-align:center;line-height:1.7">
                دسترسی دوره بر اساس وضعیت ثبت‌نام و تأیید پرداخت مدیریت می‌شود.
            </p>

            <ul>
                <li>
                    <svg class="i"><use href="#cam"/></svg>
                    <span>{{ $totalLessons > 0 ? $totalLessons . ' جلسه آموزش ویدیویی' : 'دسترسی به ویدیوها' }}</span>
                </li>
                <li>
                    <svg class="i"><use href="#inf"/></svg>
                    <span>دسترسی به سرفصل‌های منتشرشده بر اساس وضعیت ثبت‌نام</span>
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

    @if($relatedCourses->isNotEmpty())
{{-- دوره‌های مرتبط واقعی --}}
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

    </section>
@endif
</div>
@endsection
