@extends('public.layouts.academy')
@section('title', $settings->site_name.' | آموزش آنلاین')
@section('content')
<header class="hero" id="homeHero">
    <button class="arr r" type="button" data-hero-dir="1" aria-label="بعدی">
        <svg class="i"><use href="#arrr"/></svg>
    </button>
    <button class="arr l" type="button" data-hero-dir="-1" aria-label="قبلی">
        <svg class="i"><use href="#arrl"/></svg>
    </button>

    @if($banners->isNotEmpty())
        @foreach($banners as $index => $banner)
            <div class="sl {{ $index === 0 ? 'on' : '' }}" style="{{ $index % 2 === 1 ? '--a:#f97316;--b:#7c3aed' : '--a:#0d9488;--b:#1e40af' }}">
                @if($banner->image_path)
                    <img class="sl-banner-bg" src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text ?: $banner->title }}">
                @endif
                <div style="position:relative;z-index:2">
                    <span class="bd">ویژه دانش‌آموزان شیخان</span>
                    <h1>{{ $banner->title }}</h1>
                    <p>آموزش هدفمند و مرحله‌به‌مرحله با اساتید مجرب و دسترسی همیشگی به محتوای منتشرشده.</p>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:inherit">
                        @if($banner->target_url)
                            <a class="btn" href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                                مشاهده اطلاعات <svg class="i"><use href="#arrl"/></svg>
                            </a>
                        @else
                            <a class="btn" href="{{ route('public.courses.index') }}">
                                مشاهده دوره‌ها <svg class="i"><use href="#arrl"/></svg>
                            </a>
                        @endif
                        <a class="btn o" href="#quiz">پیدا کردن مسیر من</a>
                    </div>
                </div>
                <div class="vis" style="position:relative;z-index:2">
                    <i class="m"><svg class="i"><use href="#cap"/></svg></i>
                    <i class="t1"><svg class="i"><use href="#rocket"/></svg></i>
                    <i class="t2"><svg class="i"><use href="#chart"/></svg></i>
                </div>
            </div>
        @endforeach
    @else
        <!-- اسلاید ۱: مسیر هوشمند -->
        <div class="sl on" style="--a:#0d9488;--b:#1e40af">
            <div>
                <span class="bd">مسیر یادگیری هوشمند</span>
                <h1>مهارتی یاد بگیر که برایت <span style="color:var(--pri2)">آینده</span> می‌سازد</h1>
                <p>دوره‌های ساختارمند با اساتید مجرب، آزمون‌های گام‌به‌گام و دسترسی پیوسته به جلسات آموزشی.</p>
                <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:inherit">
                    <a class="btn" href="{{ route('public.courses.index') }}">مشاهده دوره‌ها <svg class="i"><use href="#arrl"/></svg></a>
                    <a class="btn o" href="#quiz">پیدا کردن مسیر من</a>
                </div>
            </div>
            <div class="vis">
                <i class="m"><svg class="i"><use href="#cap"/></svg></i>
                <i class="t1"><svg class="i"><use href="#code"/></svg></i>
                <i class="t2"><svg class="i"><use href="#chart"/></svg></i>
            </div>
        </div>

        <!-- اسلاید ۲: تمرین و آزمون -->
        <div class="sl" style="--a:#f97316;--b:#7c3aed">
            <div>
                <span class="bd">تمرین و تست‌محور</span>
                <h1>هر فصل، یک مهارت واقعی برای موفقیتت</h1>
                <p>به‌جای تماشای صرف، تمرین کن، آزمون بده و رشد درسی و تحصیلی‌ات را گام‌به‌گام بساز.</p>
                <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:inherit">
                    <a class="btn" href="{{ route('public.courses.index') }}">شروع یادگیری <svg class="i"><use href="#arrl"/></svg></a>
                    <a class="btn o" href="{{ route('public.courses.index', ['grade' => 'کنکور']) }}">دوره‌های کنکور</a>
                </div>
            </div>
            <div class="vis">
                <i class="m"><svg class="i"><use href="#rocket"/></svg></i>
                <i class="t1"><svg class="i"><use href="#award"/></svg></i>
                <i class="t2"><svg class="i"><use href="#pen"/></svg></i>
            </div>
        </div>

        <!-- اسلاید ۳: همراهی مدرسان -->
        <div class="sl" style="--a:#2563eb;--b:#0d9488">
            <div>
                <span class="bd">پشتیبانی و همراهی</span>
                <h1>مدرس کنارت است، نه فقط پشت ویدیو</h1>
                <p>سؤال بپرس، رفع اشکال کن و با نظارت مستمر اساتید مسیرت را تا رسیدن به نتیجه ادامه بده.</p>
                <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:inherit">
                    <a class="btn" href="{{ route('public.teachers.index') }}">آشنایی با مدرسان <svg class="i"><use href="#arrl"/></svg></a>
                    <a class="btn o" href="{{ route('public.contact') }}">مشاوره رایگان</a>
                </div>
            </div>
            <div class="vis">
                <i class="m"><svg class="i"><use href="#user"/></svg></i>
                <i class="t1"><svg class="i"><use href="#help"/></svg></i>
                <i class="t2"><svg class="i"><use href="#star"/></svg></i>
            </div>
        </div>
    @endif

    <div class="dots" id="heroDots"></div>
</header>
<section id="quiz" style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="qz">
            <div>
                <span class="eyebrow">کوییز ۳۰ ثانیه‌ای</span>
                <h2 style="font-size:32px;line-height:1.5;margin:8px 0;font-weight:800">کدام مسیر برای تو مناسب‌تر است؟</h2>
                <p style="color:var(--mute);line-height:1.8">به سه سؤال کوتاه پاسخ بده تا دوره و مسیر آموزشی متناسب با پایه و نیازت را ببینی.</p>
            </div>
            <div class="card qbox">
                <div class="qbar"><i id="qb"></i></div>
                <small id="qc" style="color:var(--mute);display:block;margin-bottom:6px;font-size:13px"></small>
                <p id="qt" style="font-weight:700;font-size:18px;margin:8px 0 12px;color:var(--ink)"></p>
                <div id="qo"></div>
            </div>
        </div>
    </div>
</section>
<section style="padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">دسته‌بندی‌ها</span>
                <h2>موضوع مورد علاقه‌ات را انتخاب کن</h2>
            </div>
        </div>
        <div class="g6">
            @php
                $mainCategories = [
                    ['ریاضی', 'chart', 'linear-gradient(135deg,#2563eb,#7c3aed)'],
                    ['فیزیک', 'rocket', 'linear-gradient(135deg,#0d9488,#0f766e)'],
                    ['شیمی', 'pen', 'linear-gradient(135deg,#f97316,#db2777)'],
                    ['زیست', 'user', 'linear-gradient(135deg,#16a34a,#0d9488)'],
                    ['زبان', 'lang', 'linear-gradient(135deg,#0891b2,#2563eb)'],
                    ['ادبیات', 'brief', 'linear-gradient(135deg,#7c3aed,#db2777)'],
                ];
            @endphp
            @foreach($mainCategories as $catItem)
                @php
                    $catName = $catItem[0];
                    $catIcon = $catItem[1];
                    $catGrad = $catItem[2];
                    $hasCourse = $categories->contains($catName);
                @endphp
                <a class="card cat" href="{{ route('public.courses.index', ['subject' => $catName]) }}">
                    <div class="ic" style="background:{{ $catGrad }}">
                        <svg class="i"><use href="#{{ $catIcon }}"/></svg>
                    </div>
                    <b>{{ $catName }}</b>
                    <small>{{ $hasCourse ? '۱ دوره فعال' : 'دوره‌ها' }}</small>
                </a>
            @endforeach
        </div>
    </div>
</section>

<div class="band">
    <div class="c">
        <div class="g">
            <div>
                <b>+{{ number_format(max($stats['enrollments'], 1200)) }}</b>
                <span>دانشجوی فعال</span>
            </div>
            <div>
                <b>+{{ number_format(max($stats['lessons'], 150)) }}</b>
                <span>جلسه آموزشی</span>
            </div>
            <div>
                <b>{{ number_format(max($stats['teachers'], 8)) }}</b>
                <span>مدرس متخصص</span>
            </div>
            <div>
                <b>{{ number_format(max($stats['courses'], 1)) }}</b>
                <span>دوره منتشرشده</span>
            </div>
        </div>
    </div>
</div>

<section style="padding:32px 0 64px">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">جدیدترین‌ها</span>
                <h2>دوره‌های تازه</h2>
                <p>تازه‌ترین آموزش‌های منتشر شده در آکادمی شیخان</p>
            </div>
            <a class="btn o s" style="color:var(--pri)" href="{{ route('public.courses.index') }}">
                مشاهده همه
            </a>
        </div>
        <div class="g4">
            {{-- دوره‌های واقعی دیتابیس --}}
            @foreach($courses as $course)
                <x-public.academy.course-card :course="$course" />
            @endforeach

            {{-- در صورت کم بودن دوره‌های دیتابیس، نمایش دوره‌های مبدا با اندازه استاندارد و بدون کشیدگی --}}
            @php
                $existingCount = $courses->count();
                $placeholders = [
                    ['فیزیک یازدهم و کنکور تخصصی', 'فیزیک', 'مهندس رضایی', '۲۴ جلسه', '۴٫۸', '۱٬۸۰۰', 'rocket', 'linear-gradient(135deg,#0d9488,#0f766e)', 'به‌زودی', '۷۹۰٬۰۰۰'],
                    ['شیمی جامع کنکور و تست‌های طلایی', 'شیمی', 'دکتر کریمی', '۳۰ جلسه', '۴٫۷', '۱٬۵۰۰', 'pen', 'linear-gradient(135deg,#f97316,#db2777)', 'به‌زودی', '۶۹۰٬۰۰۰'],
                    ['زیست‌شناسی مفهومی و نهایی', 'زیست', 'دکتر نوری', '۴۲ جلسه', '۴٫۹', '۲٬۶۰۰', 'user', 'linear-gradient(135deg,#16a34a,#0d9488)', 'به‌زودی', '۸۵۰٬۰۰۰'],
                ];
            @endphp
            @for($i = 0; $i < (4 - $existingCount); $i++)
                @php $ph = $placeholders[$i]; @endphp
                <article class="card cc">
                    <a class="th" href="{{ route('public.courses.index', ['subject' => $ph[1]]) }}" style="background:{{ $ph[7] }}">
                        <svg class="i"><use href="#{{ $ph[6] }}"/></svg>
                        <span class="bd">{{ $ph[8] }}</span>
                    </a>
                    <div class="cb">
                        <h3>
                            <a href="{{ route('public.courses.index', ['subject' => $ph[1]]) }}">
                                {{ $ph[0] }}
                            </a>
                        </h3>
                        <small style="color:var(--mute);margin-bottom:8px;display:block">
                            مدرس: {{ $ph[2] }}
                        </small>
                        <div class="mt">
                            <span>
                                <svg class="i"><use href="#clock"/></svg>
                                {{ $ph[3] }}
                            </span>
                            <span style="color:#f59e0b">
                                <svg class="i"><use href="#star"/></svg>
                                {{ $ph[4] }}
                            </span>
                            <span>
                                <svg class="i"><use href="#user"/></svg>
                                {{ $ph[5] }}
                            </span>
                        </div>
                        <div class="pr">
                            <div>
                                <b>{{ $ph[9] }}</b>
                                <small>تومان</small>
                            </div>
                            <a class="btn s o" style="color:var(--pri)" href="{{ route('public.courses.index', ['subject' => $ph[1]]) }}">
                                اطلاعات
                            </a>
                        </div>
                    </div>
                </article>
            @endfor
        </div>
    </div>
</section>

<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">انتخاب دانشجوها</span>
                <h2>پرطرفدارترین دوره‌ها</h2>
                <p>محبوب‌ترین دوره‌ها بر اساس استقبال و ثبت‌نام دانش‌آموزان</p>
            </div>
            <a class="btn o s" style="color:var(--pri)" href="{{ route('public.courses.index', ['sort' => 'popular']) }}">
                مشاهده پرفروش‌ترین‌ها
            </a>
        </div>
        <div class="g3">
            {{-- دوره یا دوره‌های واقعی دیتابیس --}}
            @foreach($popularCourses as $index => $course)
                @php
                    $gradients = [
                        'ریاضی' => ['linear-gradient(135deg,#2563eb,#7c3aed)', 'chart'],
                        'فیزیک' => ['linear-gradient(135deg,#0d9488,#0f766e)', 'rocket'],
                        'شیمی' => ['linear-gradient(135deg,#f97316,#db2777)', 'pen'],
                        'زیست' => ['linear-gradient(135deg,#16a34a,#0d9488)', 'user'],
                        'زبان' => ['linear-gradient(135deg,#0891b2,#2563eb)', 'lang'],
                    ];
                    $subj = $course->subject ?? '';
                    $pStyle = $gradients[$subj] ?? ['linear-gradient(135deg,#0d9488,#1e3a8a)', 'cap'];
                @endphp
                <a class="card pop" href="{{ route('public.courses.show', $course->slug) }}">
                    <span class="n">{{ $index + 1 }}</span>
                    <div class="th" style="background:{{ $pStyle[0] }}">
                        <svg class="i"><use href="#{{ $pStyle[1] }}"/></svg>
                    </div>
                    <div style="flex:1">
                        <h3>{{ $course->title }}</h3>
                        <div class="mt" style="margin-bottom:4px">
                            <span style="color:#f59e0b">
                                <svg class="i"><use href="#star"/></svg>
                                ۴٫۹
                            </span>
                            <span>
                                <svg class="i"><use href="#user"/></svg>
                                {{ number_format($course->enrollments_count ?? 0) }} دانشجو
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                            <span style="font-size:13px;color:var(--mute)">
                                {{ $course->teacher ? $course->teacher->name : 'آموزشگاه شیخان' }}
                            </span>
                            <span style="font-weight:700;color:var(--pri);font-size:14px">
                                {{ $course->type === 'free' ? 'رایگان' : number_format((int)$course->price).' ت' }}
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach

            {{-- تکمیل دوره‌های پرطرفدار مبدا تا ۶ آیتم با ابعاد ثابت --}}
            @php
                $popExisting = $popularCourses->count();
                $popSamples = [
                    ['فیزیک دوازدهم نهایی و تست', 'مهندس رضایی', '۲٬۱۰۰ دانشجو', 'rocket', 'linear-gradient(135deg,#0d9488,#0f766e)', '۸۵۰٬۰۰۰ ت'],
                    ['زیست‌شناسی جامع کنکور', 'دکتر نوری', '۳٬۲۰۰ دانشجو', 'user', 'linear-gradient(135deg,#16a34a,#0d9488)', '۱٬۱۹۰٬۰۰۰ ت'],
                    ['جمع‌بندی ریاضی و حسابان', 'دکتر احمدی', '۳٬۵۰۰ دانشجو', 'chart', 'linear-gradient(135deg,#2563eb,#7c3aed)', '۱٬۲۹۰٬۰۰۰ ت'],
                    ['واژگان و ریدینگ زبان کنکور', 'خانم صادقی', '۱٬۹۰۰ دانشجو', 'lang', 'linear-gradient(135deg,#0891b2,#2563eb)', '۵۹۰٬۰۰۰ ت'],
                    ['شیمی دوازدهم جمع‌بندی', 'دکتر کریمی', '۲٬۴۰۰ دانشجو', 'pen', 'linear-gradient(135deg,#f97316,#db2777)', '۷۹۰٬۰۰۰ ت'],
                ];
            @endphp
            @for($j = 0; $j < (6 - $popExisting); $j++)
                @php $ps = $popSamples[$j]; @endphp
                <a class="card pop" href="{{ route('public.courses.index') }}">
                    <span class="n">{{ $popExisting + $j + 1 }}</span>
                    <div class="th" style="background:{{ $ps[4] }}">
                        <svg class="i"><use href="#{{ $ps[3] }}"/></svg>
                    </div>
                    <div style="flex:1">
                        <h3>{{ $ps[0] }}</h3>
                        <div class="mt" style="margin-bottom:4px">
                            <span style="color:#f59e0b">
                                <svg class="i"><use href="#star"/></svg>
                                ۴٫۸
                            </span>
                            <span>
                                <svg class="i"><use href="#user"/></svg>
                                {{ $ps[2] }}
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">
                            <span style="font-size:13px;color:var(--mute)">
                                {{ $ps[1] }}
                            </span>
                            <span style="font-weight:700;color:var(--pri);font-size:14px">
                                {{ $ps[5] }}
                            </span>
                        </div>
                    </div>
                </a>
            @endfor
        </div>
    </div>
</section>
{{-- ۴ قدم تا تسلط --}}
<section style="padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">مسیر یادگیری</span>
                <h2>چهار قدم تا تسلط</h2>
                <p>فرآیند اصولی یادگیری در آکادمی شیخان</p>
            </div>
        </div>
        <div class="g4">
            <div class="card stp">
                <div class="n">۱</div>
                <h3>انتخاب مسیر</h3>
                <p>هدفت را بگو تا دوره و برنامه مطالعاتی پیشنهادی بگیری.</p>
            </div>
            <div class="card stp">
                <div class="n">۲</div>
                <h3>آموزش مرحله‌ای</h3>
                <p>هر درس را با ویدیوهای تدوین‌شده و سرفصل‌های معین یاد بگیر.</p>
            </div>
            <div class="card stp">
                <div class="n">۳</div>
                <h3>تمرین و آزمون</h3>
                <p>با تمرین‌های مستمر و آزمون‌های استاندارد آموخته‌هایت را بسنج.</p>
            </div>
            <div class="card stp">
                <div class="n">۴</div>
                <h3>پیگیری و پشتیبانی</h3>
                <p>سؤال بپرس، رفع اشکال کن و با نظارت مدرس به نتیجه برس.</p>
            </div>
        </div>
    </div>
</section>

{{-- پنل‌های اختصاصی --}}
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">پنل‌های اختصاصی</span>
                <h2>هر نقش، یک پنل مخصوص خودش</h2>
                <p>پس از ورود، دسترسی تو بر اساس نقش حساب مشخص می‌شود.</p>
            </div>
        </div>
        <div class="g4">
            <a class="card pn" href="{{ route('student.dashboard') }}">
                <div class="ic" style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                    <svg class="i"><use href="#cap"/></svg>
                </div>
                <h3>پنل دانش‌آموز</h3>
                <p>دوره‌ها، تکالیف، آزمون‌های آنلاین و پیگیری رشد تحصیلی.</p>
                <span class="go">ورود به پنل <svg class="i"><use href="#arrl"/></svg></span>
            </a>
            <a class="card pn" href="{{ route('teacher.dashboard') }}">
                <div class="ic" style="background:linear-gradient(135deg,#f97316,#db2777)">
                    <svg class="i"><use href="#pen"/></svg>
                </div>
                <h3>پنل مدرس</h3>
                <p>مدیریت دوره‌ها، ثبت نمرات، پاسخ به سؤالات و بررسی تکالیف.</p>
                <span class="go">ورود به پنل <svg class="i"><use href="#arrl"/></svg></span>
            </a>
            <a class="card pn" href="{{ route('guardian.dashboard') }}">
                <div class="ic" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">
                    <svg class="i"><use href="#user"/></svg>
                </div>
                <h3>پنل والدین</h3>
                <p>گزارش پیشرفت درسی، ثبت حضور و غیاب و وضعیت تحصیلی فرزند.</p>
                <span class="go">ورود به پنل <svg class="i"><use href="#arrl"/></svg></span>
            </a>
            <a class="card pn" href="{{ route('admin.dashboard') }}">
                <div class="ic" style="background:linear-gradient(135deg,#0891b2,#2563eb)">
                    <svg class="i"><use href="#chart"/></svg>
                </div>
                <h3>پنل مدیریت</h3>
                <p>نظارت بر کاربران، انتشار دوره‌ها، گزارش‌های مالی و پشتیبانی.</p>
                <span class="go">ورود به پنل <svg class="i"><use href="#arrl"/></svg></span>
            </a>
        </div>
    </div>
</section>

{{-- نظرات دانش‌آموزان --}}
<section style="padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">نظرات</span>
                <h2>دانشجوها چه می‌گویند؟</h2>
                <p>تجربه واقعی دانش‌آموزان از دوره‌ها و آزمون‌های شیخان</p>
            </div>
        </div>
        <div class="g3">
            <div class="card ts">
                <div class="stars">★★★★★</div>
                <p>«ساختار فصل‌ها و پشتیبانی مستقیم استاد دقیقاً همان چیزی بود که برای درس ریاضی لازم داشتم؛ تراز نهایی‌ام خیلی رشد کرد.»</p>
                <div class="who">
                    <div class="av" style="background:linear-gradient(135deg,#0d9488,#0f766e)">س</div>
                    <div>
                        <b>سارا احمدی</b>
                        <small style="color:var(--mute);display:block">دانش‌آموز پایه دوازدهم</small>
                    </div>
                </div>
            </div>
            <div class="card ts">
                <div class="stars">★★★★★</div>
                <p>«کوییزها و آزمون‌های مرحله‌ای باعث شد نقاط ضعفم را قبل از امتحانات پیدا کنم. پنل کاربری هم کار با ویدیوها را خیلی راحت کرده.»</p>
                <div class="who">
                    <div class="av" style="background:linear-gradient(135deg,#f97316,#db2777)">ع</div>
                    <div>
                        <b>علی رضایی</b>
                        <small style="color:var(--mute);display:block">داوطلب کنکور تجربی</small>
                    </div>
                </div>
            </div>
            <div class="card ts">
                <div class="stars">★★★★★</div>
                <p>«پاسخگویی مدرسان به سوالات سریع و دقیق است. گزارش‌های پنل والدین هم به ما کمک کرد پیگیر وضعیت درسی فرزندمان باشیم.»</p>
                <div class="who">
                    <div class="av" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">م</div>
                    <div>
                        <b>مریم کاظمی</b>
                        <small style="color:var(--mute);display:block">ولی دانش‌آموز</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- مدرسان شیخان --}}
@if($teachers->isNotEmpty())
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">مدرسان برتر</span>
                <h2>با مدرس‌های شیخان آشنا شو</h2>
                <p>یادگیری از اساتید باسابقه و متخصص در هر رشته</p>
            </div>
            <a class="btn o s" style="color:var(--pri)" href="{{ route('public.teachers.index') }}">
                همه مدرسان
            </a>
        </div>
        <div class="g4">
            @foreach($teachers as $teacher)
                <div class="card tcard">
                    <div class="av" style="background:linear-gradient(135deg,var(--pri),var(--acc))">
                        {{ mb_substr($teacher->name, 0, 1) }}
                    </div>
                    <h3>{{ $teacher->name }}</h3>
                    <span class="bd">مدرس {{ $teacher->subject ?: 'تخصصی' }}</span>
                    <div class="mt">
                        <span style="color:#f59e0b">
                            <svg class="i"><use href="#star"/></svg>
                            ۴٫۹
                        </span>
                        <span>
                            <svg class="i"><use href="#cam"/></svg>
                            {{ $teacher->published_courses_count ?? 1 }} دوره
                        </span>
                    </div>
                    <a class="btn o s" style="color:var(--pri);width:100%" href="{{ route('public.teachers.show', $teacher->id) }}">
                        مشاهده پروفایل
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- چرا شیخان؟ --}}
<section style="padding:64px 0">
    <div class="c">
        <div class="why">
            <div>
                <span class="eyebrow">چرا شیخان؟</span>
                <h2>آموزشی که تا رسیدن به نتیجه کنارت می‌ماند</h2>
                <p>در آموزشگاه شیخان فقط ویدیو نمی‌بینی؛ تمرین می‌کنی، بازخورد می‌گیری و رشدت را قدم‌به‌قدم می‌بینی.</p>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a class="btn" href="{{ route('public.courses.index') }}">مشاهده دوره‌ها</a>
                    <a class="btn o" style="color:var(--pri)" href="{{ route('public.contact') }}">مشاوره رایگان</a>
                </div>
            </div>
            <div class="g3" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                <div class="card wf">
                    <div class="ic" style="background:linear-gradient(135deg,#0d9488,#0f766e)">
                        <svg class="i"><use href="#award"/></svg>
                    </div>
                    <h3>مدرسان منتخب</h3>
                    <p>مدرس‌های باسابقه و بررسی‌شده برای هر درس و پایه.</p>
                </div>
                <div class="card wf">
                    <div class="ic" style="background:linear-gradient(135deg,#f97316,#db2777)">
                        <svg class="i"><use href="#cam"/></svg>
                    </div>
                    <h3>دسترسی همیشگی</h3>
                    <p>جلسه‌ها را هر زمان و هر چندبار که خواستی آنلاین ببین.</p>
                </div>
                <div class="card wf">
                    <div class="ic" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">
                        <svg class="i"><use href="#chart"/></svg>
                    </div>
                    <h3>پیگیری رشد تحصیلی</h3>
                    <p>نمودار پیشرفت و آزمون‌ها در پنل اختصاصی دانشجو.</p>
                </div>
                <div class="card wf">
                    <div class="ic" style="background:linear-gradient(135deg,#0891b2,#2563eb)">
                        <svg class="i"><use href="#user"/></svg>
                    </div>
                    <h3>گزارش برای والدین</h3>
                    <p>پیشرفت، آزمون‌ها و حضور فرزند در پنل والدین.</p>
                </div>
                <div class="card wf">
                    <div class="ic" style="background:linear-gradient(135deg,#7c3aed,#db2777)">
                        <svg class="i"><use href="#pen"/></svg>
                    </div>
                    <h3>آزمون و تمرین</h3>
                    <p>تمرین منظم با سنجش دقیق و بازخورد مستقیم مدرس.</p>
                </div>
                <div class="card wf">
                    <div class="ic" style="background:linear-gradient(135deg,#16a34a,#0d9488)">
                        <svg class="i"><use href="#help"/></svg>
                    </div>
                    <h3>پشتیبانی سریع</h3>
                    <p>پاسخ سؤال‌های درسی‌ات در سریع‌ترین زمان ممکن.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- مجله شیخان (مقالات سئو) --}}
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">مجله شیخان</span>
                <h2>تازه‌ترین مقالات آموزشی</h2>
                <p>نکته‌های مطالعه، برنامه‌ریزی درسی و آمادگی کنکور</p>
            </div>
            <a class="btn o s" style="color:var(--pri)" href="{{ route('seo.articles.index') }}">
                همه مقالات
            </a>
        </div>
        <div class="g3">
            @if($articles->isNotEmpty())
                @foreach($articles as $article)
                    <article class="card cc">
                        @if($article->featured_image_path)
                            <a class="th" href="{{ route('seo.articles.show', $article->slug) }}">
                                <img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" style="width:100%;height:100%;object-fit:cover">
                            </a>
                        @else
                            <a class="th" href="{{ route('seo.articles.show', $article->slug) }}" style="background:linear-gradient(135deg,#0d9488,#2563eb)">
                                <svg class="i"><use href="#pen"/></svg>
                                <span class="bd bd-article">مقاله</span>
                            </a>
                        @endif
                        <div class="cb">
                            <h3>
                                <a href="{{ route('seo.articles.show', $article->slug) }}">
                                    {{ $article->title }}
                                </a>
                            </h3>
                            <div class="mt">
                                <span>{{ $article->published_at ? $article->published_at->format('Y/m/d') : 'امروز' }}</span>
                                <span>۶ دقیقه مطالعه</span>
                            </div>
                            <p style="color:var(--mute);font-size:13px;line-height:1.8;margin:0 0 14px">
                                {{ Str::limit(strip_tags($article->excerpt ?: $article->content ?? ''), 95) }}
                            </p>
                            <div class="pr">
                                <a class="btn s" href="{{ route('seo.articles.show', $article->slug) }}">
                                    ادامه مطلب
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            @else
                <article class="card cc">
                    <div class="th" style="background:linear-gradient(135deg,#0d9488,#2563eb)">
                        <svg class="i"><use href="#pen"/></svg>
                        <span class="bd">روش مطالعه</span>
                    </div>
                    <div class="cb">
                        <h3>چطور برای کنکور برنامه مطالعاتی واقعی بنویسیم؟</h3>
                        <div class="mt"><span>۱۴ آبان</span><span>۸ دقیقه مطالعه</span></div>
                        <p style="color:var(--mute);font-size:13px;line-height:1.8;margin:0 0 14px">
                            برنامه‌ای که در روزهای سخت هم قابل اجرا باشد، از برنامه‌ای که فقط روی کاغذ عالی است بهتر است.
                        </p>
                        <div class="pr"><a class="btn s" href="{{ route('seo.articles.index') }}">ادامه مطلب</a></div>
                    </div>
                </article>
                <article class="card cc">
                    <div class="th" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">
                        <svg class="i"><use href="#chart"/></svg>
                        <span class="bd">کنکور</span>
                    </div>
                    <div class="cb">
                        <h3>ریاضی کنکور را از کجا و با چه مباحثی شروع کنیم؟</h3>
                        <div class="mt"><span>۱۰ آبان</span><span>۶ دقیقه مطالعه</span></div>
                        <p style="color:var(--mute);font-size:13px;line-height:1.8;margin:0 0 14px">
                            بررسی زنجیره پیش‌نیازهای ریاضیات و بودجه‌بندی تست‌های پرامتیاز در آزمون سراسری.
                        </p>
                        <div class="pr"><a class="btn s" href="{{ route('seo.articles.index') }}">ادامه مطلب</a></div>
                    </div>
                </article>
                <article class="card cc">
                    <div class="th" style="background:linear-gradient(135deg,#f97316,#db2777)">
                        <svg class="i"><use href="#clock"/></svg>
                        <span class="bd">سلامت و تمرکز</span>
                    </div>
                    <div class="cb">
                        <h3>خواب شب امتحان و راه‌های افزایش بازدهی حافظه</h3>
                        <div class="mt"><span>۷ آبان</span><span>۵ دقیقه مطالعه</span></div>
                        <p style="color:var(--mute);font-size:13px;line-height:1.8;margin:0 0 14px">
                            روش‌های کاربردی برای کاهش استرس، تثبیت مطالب در حافظه بلندمدت و استراحت هدفمند.
                        </p>
                        <div class="pr"><a class="btn s" href="{{ route('seo.articles.index') }}">ادامه مطلب</a></div>
                    </div>
                </article>
            @endif
        </div>
    </div>
</section>

{{-- بنر اقدام به ثبت‌نام (CTA) --}}
<section style="padding:64px 0">
    <div class="c">
        <div class="cta">
            <h2>اولین جلسه را رایگان ببین</h2>
            <p>بدون نیاز به ثبت کارت بانکی و بدون تعهد، تدریس اساتید شیخان را بررسی کن.</p>
            <a class="btn w" href="{{ route('public.courses.index') }}">
                شروع یادگیری <svg class="i"><use href="#arrl"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- سؤالات متداول (FAQ) --}}
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="faq">
            <div>
                <span class="eyebrow">سؤالات متداول</span>
                <h2 style="font-size:30px;line-height:1.6;margin:8px 0;font-weight:800">قبل از شروع، این‌ها را بدان</h2>
                <p style="color:var(--mute);margin:0 0 20px;line-height:1.9">
                    جواب سؤالت اینجا نبود؟ تیم پشتیبانی شیخان همیشه برای راهنمایی در کنارت است.
                </p>
                <a class="btn" href="{{ route('public.contact') }}">تماس با پشتیبانی</a>
            </div>
            <div>
                <details open>
                    <summary>چطور در دوره‌ها ثبت‌نام کنم؟</summary>
                    <div class="fa">با شماره موبایل وارد شو، دوره مدنظرت را انتخاب و ثبت‌نام کن. دسترسی به جلسات بلافاصله در پنل دانش‌آموز فعال می‌شود.</div>
                </details>
                <details>
                    <summary>تا کی به ویدیوها و محتوای دوره دسترسی دارم؟</summary>
                    <div class="fa">دسترسی به دوره‌های خریداری‌شده همیشگی است و می‌توانی هر زمان و هر چندبار که خواستی جلسه‌ها را مرور کنی.</div>
                </details>
                <details>
                    <summary>اگر در مورد درس‌ها سؤال داشتم چطور بپرسم؟</summary>
                    <div class="fa">از طریق پنل کاربری یا سامانه پشتیبانی اختصاصی هر دوره می‌توانی مستقیماً از مدرس سؤال بپرسی و رفع اشکال کنی.</div>
                </details>
                <details>
                    <summary>والدین چطور پیشرفت فرزندشان را پیگیری می‌کنند؟</summary>
                    <div class="fa">در پنل اختصاصی والدین، گزارش حضور در کلاس‌ها، نمرات آزمون‌ها و نمودار رشد تحصیلی در یک نگاه قابل مشاهده است.</div>
                </details>
                <details>
                    <summary>آیا آزمون‌ها دارای کارنامه و پاسخ تشریحی هستند؟</summary>
                    <div class="fa">بله، بلافاصله پس از اتمام آزمون، کارنامه تحلیلی به همراه پاسخ تشریحی تک‌تک سؤالات در پنل قرار می‌گیرد.</div>
                </details>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const hero = document.getElementById('homeHero');
    if (!hero) return;

    const slides = hero.querySelectorAll('.sl');
    const dotsContainer = document.getElementById('heroDots');
    if (!slides.length || !dotsContainer) return;

    let currentIndex = 0;
    let timer = null;

    dotsContainer.innerHTML = '';
    slides.forEach((_, idx) => {
        const dot = document.createElement('i');
        if (idx === 0) dot.classList.add('on');
        dot.addEventListener('click', () => {
            goToSlide(idx);
            resetAutoplay();
        });
        dotsContainer.appendChild(dot);
    });

    const dots = dotsContainer.querySelectorAll('i');

    function goToSlide(n) {
        currentIndex = (n + slides.length) % slides.length;
        slides.forEach((sl, idx) => {
            sl.classList.toggle('on', idx === currentIndex);
        });
        dots.forEach((dt, idx) => {
            dt.classList.toggle('on', idx === currentIndex);
        });
    }

    hero.querySelectorAll('[data-hero-dir]').forEach(btn => {
        btn.addEventListener('click', function () {
            const dir = parseInt(this.getAttribute('data-hero-dir'), 10) || 1;
            goToSlide(currentIndex + dir);
            resetAutoplay();
        });
    });

    function startAutoplay() {
        if (slides.length <= 1) return;
        timer = setInterval(() => {
            goToSlide(currentIndex + 1);
        }, 6500);
    }

    function resetAutoplay() {
        if (timer) clearInterval(timer);
        startAutoplay();
    }

    startAutoplay();

    // Quiz Interactive Logic
    (function () {
        const qb = document.getElementById('qb');
        const qc = document.getElementById('qc');
        const qt = document.getElementById('qt');
        const qo = document.getElementById('qo');
        if (!qb || !qc || !qt || !qo) return;

        const baseCoursesUrl = "{{ route('public.courses.index') }}";

        const questions = [
            {
                title: "پایه یا مقطع تحصیلی‌ات چیست؟",
                options: [
                    { label: "پایه دهم", grade: "دهم" },
                    { label: "پایه یازدهم", grade: "یازدهم" },
                    { label: "پایه دوازدهم", grade: "دوازدهم" },
                    { label: "پایه سیزدهم (کنکور)", grade: "کنکور" }
                ]
            },
            {
                title: "بیشتر در چه درسی نیاز به تقویت داری؟",
                options: [
                    { label: "ریاضی و حسابان", subject: "ریاضی" },
                    { label: "فیزیک تخصصی", subject: "فیزیک" },
                    { label: "شیمی و زیست‌شناسی", subject: "شیمی" },
                    { label: "زبان انگلیسی و عمومی", subject: "زبان" }
                ]
            },
            {
                title: "هدف اصلی‌ات از شرکت در دوره چیست؟",
                options: [
                    { label: "تسلط بر تست‌زنی و رتبه برتر کنکور", goal: "test" },
                    { label: "نمره ۲۰ امتحان نهایی و تشریحی", goal: "final" },
                    { label: "یادگیری مفهومی و حل مسئله از صفر", goal: "concept" },
                    { label: "جمع‌بندی سریع و مرور شب امتحان", goal: "review" }
                ]
            }
        ];

        let currentStep = 0;
        let userAnswers = {};

        function renderQuiz() {
            const progress = (currentStep / questions.length) * 100;
            qb.style.width = progress + "%";

            if (currentStep >= questions.length) {
                qc.textContent = "";
                qt.textContent = "";

                const subject = userAnswers.subject || "ریاضی";
                const grade = userAnswers.grade || "کنکور";
                const targetUrl = `${baseCoursesUrl}?subject=${encodeURIComponent(subject)}&grade=${encodeURIComponent(grade)}`;

                qo.innerHTML = `
                    <div class="res">
                        <div class="big">
                            <svg class="i" style="width:34px;height:34px"><use href="#award"/></svg>
                        </div>
                        <b style="font-size:18px;display:block;margin-bottom:6px">مسیر پیشنهادی تو آماده است!</b>
                        <p style="color:var(--mute);margin:4px 0 16px;font-size:15px">
                            دوره تخصصی <strong>${subject}</strong> ویژه <strong>${grade}</strong>
                        </p>
                        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                            <a class="btn" href="${targetUrl}">
                                مشاهده دوره‌های مرتبط <svg class="i"><use href="#arrl"/></svg>
                            </a>
                            <button class="btn o s" style="color:var(--pri)" type="button" id="qaBtn">
                                شروع دوباره
                            </button>
                        </div>
                    </div>
                `;

                document.getElementById('qaBtn')?.addEventListener('click', function () {
                    currentStep = 0;
                    userAnswers = {};
                    renderQuiz();
                });
                return;
            }

            const q = questions[currentStep];
            qc.textContent = `سؤال ${currentStep + 1} از ${questions.length}`;
            qt.textContent = q.title;

            const letters = ["الف", "ب", "ج", "د"];
            qo.innerHTML = q.options.map((opt, i) => `
                <button class="opt" type="button" data-idx="${i}">
                    <b>${letters[i] || (i + 1)}</b>
                    <span>${opt.label}</span>
                </button>
            `).join('');

            qo.querySelectorAll('.opt').forEach((btn, idx) => {
                btn.addEventListener('click', function () {
                    const selectedOpt = q.options[idx];
                    if (selectedOpt.grade) userAnswers.grade = selectedOpt.grade;
                    if (selectedOpt.subject) userAnswers.subject = selectedOpt.subject;
                    if (selectedOpt.goal) userAnswers.goal = selectedOpt.goal;

                    currentStep++;
                    renderQuiz();
                });
            });
        }

        renderQuiz();
    })();
});
</script>
@endpush
