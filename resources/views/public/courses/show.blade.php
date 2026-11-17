@extends('public.layouts.academy')

@section('title', $course->title . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'شیخان'))
@section('description', \Illuminate\Support\Str::limit(strip_tags($course->description ?: $course->title), 155))
@section('canonical', route('public.courses.show', $course->slug))
@if(!empty($isAdminPreview))@section('robots', 'noindex,nofollow')@endif

@section('content')
@if(!empty($isAdminPreview))
    <div class="c" style="padding-top:16px"><div role="status" style="padding:14px 18px;border-radius:14px;background:#fff7e6;border:1px solid #f0d49a;color:#7a4b08;display:flex;align-items:center;gap:10px"><strong>پیش‌نمایش مدیر</strong><span>این همان قالب صفحه عمومی دوره است؛ این دوره هنوز برای بازدیدکنندگان منتشر نشده است.</span></div></div>
@endif
@php
    $teacher = $course->teacher;
    $learningBenefits = [];
    if ($features['recorded_video'] ?? false) $learningBenefits[] = 'آموزش ویدیویی فصل‌به‌فصل با دسترسی کنترل‌شده';
    if ($features['live_class'] ?? false) $learningBenefits[] = 'جلسات آنلاین زمان‌بندی‌شده و پیگیری حضور در کلاس';
    if ($features['resources'] ?? false) $learningBenefits[] = 'جزوه‌ها، فایل‌ها و منابع تکمیلی آموزشی';
    if ($features['assignments'] ?? false) $learningBenefits[] = 'تمرین، تکلیف و بازخورد مدرس';
    if ($features['exams'] ?? false) $learningBenefits[] = 'آزمون و کوییز با ثبت نتیجه در کارنامه';
    if ($features['projects'] ?? false) $learningBenefits[] = 'پروژه‌های مرحله‌ای و ارزیابی پیشرفت';
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
                {{ $totalLessons > 0 ? $totalLessons . ' درس آموزشی' : 'دوره جامع' }}
            </span>
            @if($course->grade)
                <span class="bd">پایه {{ $course->grade }}</span>
            @endif
        </div>
        <div class="mt" aria-label="امکانات و شیوه ارائه دوره">
            @if($features['recorded_video'] ?? false)<span>آموزش ویدیویی</span>@endif
            @if($features['live_class'] ?? false)<span>آموزش زنده</span>@endif
            @if($features['resources'] ?? false)<span>منابع آموزشی</span>@endif
            @if($features['assignments'] ?? false)<span>تکلیف و تمرین</span>@endif
            @if($features['exams'] ?? false)<span>آزمون و کوییز</span>@endif
            @if($features['projects'] ?? false)<span>پروژه مرحله‌ای</span>@endif
            <span>{{ number_format($course->active_classrooms_count ?? 0) }} گروه کلاسی فعال</span>
            @if($features['live_class'] ?? false)<span>{{ number_format($upcomingOnlineSessionCount) }} جلسه آنلاین پیش‌رو</span>@endif
        </div>
    </div>
</div>

{{-- تب‌بار ناوبری سریع سرفصل‌ها --}}
<div class="stab">
    <div class="c">
        @if($course->description)<a href="#c1">معرفی دوره</a>@endif
        <a href="#c2">سرفصل‌ها ({{ $sections->total() }} فصل)</a>
        @if($features['live_class'] ?? false)<a href="#live-sessions">برنامه کلاس‌ها</a>@endif
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


            @if(count($learningBenefits))
                <div class="card blk" id="c1-learning">
                    <h2>امکانات آموزشی این دوره</h2>
                    <ul class="learn">
                        @foreach($learningBenefits as $benefit)
                            <li>
                                <svg class="i"><use href="#chk"/></svg>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- بخش ۲: سرفصل‌ها و جلسات دوره --}}
            <div class="card blk" id="c2">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;flex-wrap:wrap;gap:12px">
                    <h2 style="margin:0">سرفصل‌ها و جلسات دوره</h2>
                    <div class="curriculum-stats">
                        <span class="c-badge">
                            <svg class="i" style="width:14px;height:14px"><use href="#brief"/></svg>
                            {{ $sections->total() }} فصل
                        </span>
                        <span class="c-badge">
                            <svg class="i" style="width:14px;height:14px"><use href="#cam"/></svg>
                            {{ $totalLessons }} درس آموزشی
                        </span>
                    </div>
                </div>

                @if($sections->isNotEmpty())
                    <div class="sec-list">
                        @foreach($sections as $index => $section)
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
                                        <span class="sec-count">{{ $section->published_lessons_count }} جلسه</span>
                                        @if($sectionDuration > 0 && $section->published_lessons_count <= $sectionLessons->count())
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
                                                    $isUnlocked = (bool) $hasCourseAccess
                                                        && (! ($features['recorded_video'] ?? false) || (bool) $canWatchCourseVideos);
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
                                                                <span class="lesson-badge-locked" aria-label="این درس قفل است"><svg class="i" aria-hidden="true" style="width:15px;height:15px;vertical-align:-3px;margin-inline-end:5px"><use href="#lock"/></svg>ویژه دانش‌آموزان ثبت‌نام‌شده</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @if(filled($lesson->body))
                                                        <p class="lesson-description" style="margin:10px 0 0 42px;color:var(--mute);font-size:13px;line-height:1.9;white-space:pre-line">{{ $lesson->body }}</p>
                                                    @endif

                                                    <div class="lesson-meta">
                                                        <span class="lesson-time">
                                                            <svg class="i" style="width:14px;height:14px"><use href="#clock"/></svg>
                                                            {{ $lesson->duration_minutes ? $lesson->duration_minutes . ' دقیقه' : 'ویدیویی' }}
                                                        </span>
                                                        @if($isUnlocked)
                                                            <a class="btn s" href="{{ route('public.courses.show', $course->slug).'#c2' }}">ادامه یادگیری در همین صفحه</a>
                                                        @else
                                                            <span class="lesson-lock-label"><svg class="i" aria-hidden="true" style="width:15px;height:15px;vertical-align:-3px;margin-inline-end:5px"><use href="#lock"/></svg>پس از ثبت‌نام فعال می‌شود</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($section->published_lessons_count > $sectionLessons->count())
                                            @if ($hasCourseAccess)
                                                <a class="btn g s" href="{{ route('student.courses.show', $course) }}">مشاهده همه سرفصل‌ها</a>
                                            @else
                                                <div class="lesson-empty">برای مشاهده همه درس‌ها و ادامه آموزش، ثبت‌نام دوره را تکمیل کنید.</div>
                                            @endif
                                        @endif
                                    @else
                                        <div class="lesson-empty">جلسات این فصل به‌زودی ضبط و بارگذاری خواهد شد.</div>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                    @if ($sections->hasPages())
                        <div class="course-curriculum-pagination">{{ $sections->links() }}</div>
                    @endif
                @else
                    <div class="curriculum-empty">
                        <svg class="i" style="width:36px;height:36px;color:var(--mute);margin-bottom:8px"><use href="#brief"/></svg>
                        <p style="margin:0">سرفصل‌های آموزشی این دوره در حال تدوین و بارگذاری است.</p>
                    </div>
                @endif
            </div>

            @if($features['live_class'] ?? false)
                <section class="card blk" id="live-sessions">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:18px">
                        <div>
                            <h2 style="margin:0 0 6px">برنامه کلاس‌های آنلاین</h2>
                            <p class="mu" style="margin:0">زمان‌بندی گروه‌های فعال این دوره؛ ورود به جلسه فقط برای اعضای مجاز همان کلاس فعال است.</p>
                        </div>
                        <span class="c-badge">{{ number_format($upcomingSessions->count()) }} جلسه پیش‌رو</span>
                    </div>
                    @forelse($upcomingSessions as $session)
                        @php
                            $joinReady = filled($session->meeting_url)
                                || ($session->meeting_provider === 'skyroom'
                                    && filled($session->classroom?->skyroom_room_id)
                                    && $session->classroom?->skyroom_status === 'ready');
                        @endphp
                        <article style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-top:1px solid var(--ln);flex-wrap:wrap">
                            <div style="min-width:220px;flex:1">
                                <strong style="display:block">{{ $session->title }}</strong>
                                <span class="mu" style="font-size:13px">{{ $session->can_join ? ($session->classroom?->name ?? 'گروه آموزشی شما') : 'گروه آموزشی' }}</span>
                                <div style="margin-top:5px;font-size:13px;color:var(--mute)">
                                    {{ $session->starts_at?->translatedFormat('l d F Y، H:i') }}
                                    @if($session->ends_at) — {{ $session->ends_at->translatedFormat('H:i') }} @endif
                                </div>
                            </div>
                            @if(auth()->check() && auth()->user()->hasRole('student') && $session->can_join && $joinReady)
                                <form method="POST" action="{{ route('student.class-sessions.join', $session) }}" target="_blank" rel="noopener">
                                    @csrf
                                    <button type="submit" class="btn s">ورود به کلاس</button>
                                </form>
                            @elseif(auth()->check() && auth()->user()->hasRole('student') && $session->can_join)
                                <span class="lesson-lock-label">{{ $session->meeting_provider === 'jitsi' ? 'لینک Jitsi هنوز توسط مدرس ثبت نشده است' : 'اتصال Skyroom در انتظار فعال‌سازی سرویس' }}</span>
                            @elseif(auth()->check() && auth()->user()->hasRole('student') && $hasCourseAccess)
                                <span class="lesson-lock-label">برای ورود، عضویت در گروه این کلاس لازم است</span>
                            @else
                                <span class="lesson-lock-label">پس از ثبت‌نام و تخصیص گروه فعال می‌شود</span>
                            @endif
                        </article>
                    @empty
                        <div class="lesson-empty">هنوز جلسه‌ای برای گروه‌های این دوره زمان‌بندی نشده است. برنامه کلاس پس از ثبت توسط آموزش در همین صفحه نمایش داده می‌شود.</div>
                    @endforelse
                </section>
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
                    <button class="btn" style="width:100%;justify-content:center;font-size:15px" type="submit">افزودن به سبد خرید</button>
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
                @if($features['recorded_video'] ?? false)
                    <li><svg class="i"><use href="#cam"/></svg><span>{{ $totalLessons }} درس ویدیویی / آموزشی</span></li>
                @endif
                @if($features['live_class'] ?? false)
                    <li><svg class="i"><use href="#users"/></svg><span>جلسات زنده و گروه‌های کلاسی</span></li>
                @endif
                @if($features['resources'] ?? false)
                    <li><svg class="i"><use href="#inf"/></svg><span>جزوه و منابع تکمیلی</span></li>
                @endif
                @if($features['assignments'] ?? false)
                    <li><svg class="i"><use href="#file"/></svg><span>تمرین و تکلیف با بازخورد مدرس</span></li>
                @endif
                @if($features['exams'] ?? false)
                    <li><svg class="i"><use href="#award"/></svg><span>آزمون و کوییز و ثبت نتیجه</span></li>
                @endif
                @if($features['projects'] ?? false)
                    <li><svg class="i"><use href="#check"/></svg><span>پروژه و ارزیابی مرحله‌ای</span></li>
                @endif
            </ul>
            @if(($features['recorded_video'] ?? false) && $hasCourseAccess && ! $canWatchCourseVideos)
                <p class="lesson-empty" style="margin-top:14px">ثبت‌نام شما فعال است؛ پخش ویدیو پس از صدور یا تأیید لایسنس SpotPlayer در همین صفحه فعال می‌شود.</p>
            @endif
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
        </div>
    </section>
@endif

    @if ($canWatchCourseVideos)
        <button
            type="button"
            id="course-video-launcher"
            class="btn"
            style="position:fixed;z-index:80;inset-inline-end:24px;bottom:24px;box-shadow:0 12px 32px rgba(15,23,42,.22)"
            aria-haspopup="dialog"
            aria-controls="course-video-dialog"
        >
            <svg class="i"><use href="#play"/></svg>
            تماشای ویدیوهای دوره
        </button>

        <dialog id="course-video-dialog" style="width:min(920px,calc(100% - 24px));max-width:920px;border:0;border-radius:20px;padding:0;box-shadow:0 24px 80px rgba(15,23,42,.32)">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 20px;border-bottom:1px solid var(--ln,#e5e7eb)">
                <strong>ویدیوهای {{ $course->title }}</strong>
                <button type="button" id="course-video-close" class="btn g s" aria-label="بستن پنجره پخش">بستن</button>
            </div>
            <div style="display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:16px;padding:16px">
                <div style="min-width:0">
                    <div style="aspect-ratio:16/9;background:#0f172a;border-radius:12px;overflow:hidden">
                        <iframe
                            id="course-video-frame"
                            title="پخش ویدیوی دوره"
                            src="about:blank"
                            style="width:100%;height:100%;border:0"
                            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
                            allowfullscreen
                            referrerpolicy="strict-origin-when-cross-origin"
                            sandbox="allow-scripts allow-same-origin allow-presentation"
                        ></iframe>
                    </div>
                    <p class="mu" style="font-size:12px;margin:10px 0 0">پخش فقط با ثبت‌نام فعال و لایسنس معتبر ارائه‌دهنده فعال می‌شود.</p>
                </div>
                <nav aria-label="فهرست ویدیوهای دوره" style="display:flex;flex-direction:column;gap:8px;max-height:440px;overflow:auto">
                    @foreach ($playableVideos as $video)
                        <button
                            type="button"
                            class="btn g s course-video-choice"
                            data-player-url="{{ route('public.courses.videos.play', ['slug' => $course->slug, 'video' => $video->id]) }}"
                            style="justify-content:flex-start;text-align:right;white-space:normal"
                        >{{ $video->title }}</button>
                    @endforeach
                </nav>
            </div>
        </dialog>
        <script>
            (() => {
                const dialog = document.getElementById('course-video-dialog');
                const launcher = document.getElementById('course-video-launcher');
                const close = document.getElementById('course-video-close');
                const frame = document.getElementById('course-video-frame');
                if (!dialog || !launcher || !close || !frame) return;

                launcher.addEventListener('click', () => {
                    dialog.showModal();
                    const firstVideo = dialog.querySelector('[data-player-url]');
                    if (firstVideo) frame.src = firstVideo.dataset.playerUrl;
                });
                dialog.querySelectorAll('[data-player-url]').forEach((button) => {
                    button.addEventListener('click', () => {
                        frame.src = button.dataset.playerUrl;
                    });
                });
                const closeDialog = () => {
                    dialog.close();
                    frame.src = 'about:blank';
                };
                close.addEventListener('click', closeDialog);
                dialog.addEventListener('click', (event) => {
                    if (event.target === dialog) closeDialog();
                });
                dialog.addEventListener('close', () => { frame.src = 'about:blank'; });
            })();
        </script>
    @endif
</div>
@endsection
