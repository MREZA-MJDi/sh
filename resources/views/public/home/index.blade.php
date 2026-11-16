@extends('public.layouts.academy')
@section('title', $settings->site_name.' | آموزش آنلاین')
@section('content')
@php($homeContent = $homeContent ?? (is_array($settings->home_sections) ? $settings->home_sections : \\App\\Models\\SeoSetting::defaultHomeSections()))
@if($homeBanners->isNotEmpty())
<header class="hero" id="homeHero">
    <button class="arr r" type="button" data-hero-dir="1" aria-label="بعدی">
        <svg class="i"><use href="#arrr"/></svg>
    </button>
    <button class="arr l" type="button" data-hero-dir="-1" aria-label="قبلی">
        <svg class="i"><use href="#arrl"/></svg>
    </button>

    @foreach($homeBanners as $index => $banner)
            <div class="sl {{ $index === 0 ? 'on' : '' }}" style="{{ $index % 2 === 1 ? '--a:#f97316;--b:#7c3aed' : '--a:#0d9488;--b:#1e40af' }}">
                @if($banner->image_path)
                    <img class="sl-banner-bg" src="{{ Storage::disk('public')->url($banner->image_path) }}" style="object-position: {{ $banner->image_position ?? '50% 50%' }}" alt="{{ $banner->alt_text ?: $banner->title }}">
                @endif
                <div style="position:relative;z-index:2">
                    <span class="bd">{{ $homeContent['hero_badge'] }}</span>
                    <h1>{{ $banner->title }}</h1>
                    <p>{{ $homeContent['hero_description'] }}</p>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:inherit">
                        @if($banner->target_url)
                            <a class="btn" href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>
                                {{ $homeContent['hero_primary_label'] }} <svg class="i"><use href="#arrl"/></svg>
                            </a>
                        @else
                            <a class="btn" href="{{ route('public.courses.index') }}">
                                {{ $homeContent['hero_primary_label'] }} <svg class="i"><use href="#arrl"/></svg>
                            </a>
                        @endif
                        <a class="btn o" href="{{ route('public.contact') }}">{{ $homeContent['hero_secondary_label'] }}</a>
                    </div>
                </div>
                <div class="vis" style="position:relative;z-index:2">
                    <i class="m"><svg class="i"><use href="#cap"/></svg></i>
                    <i class="t1"><svg class="i"><use href="#rocket"/></svg></i>
                    <i class="t2"><svg class="i"><use href="#chart"/></svg></i>
                </div>
            </div>
    @endforeach

    <div class="dots" id="heroDots"></div>
</header>
@endif
<section style="padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">{{ $homeContent['categories_eyebrow'] }}</span>
                <h2>{{ $homeContent['categories_title'] }}</h2>
            </div>
        </div>
        <div class="g6">
            @php
                $categoryStyles = [
                    'ریاضی' => ['chart', 'linear-gradient(135deg,#2563eb,#7c3aed)'],
                    'فیزیک' => ['rocket', 'linear-gradient(135deg,#0d9488,#0f766e)'],
                    'شیمی' => ['pen', 'linear-gradient(135deg,#f97316,#db2777)'],
                    'زیست' => ['user', 'linear-gradient(135deg,#16a34a,#0d9488)'],
                    'زبان' => ['lang', 'linear-gradient(135deg,#0891b2,#2563eb)'],
                    'ادبیات' => ['brief', 'linear-gradient(135deg,#7c3aed,#db2777)'],
                ];
            @endphp
            @foreach($categories as $catName)
                @php($categoryStyle = $categoryStyles[$catName] ?? ['cap', 'linear-gradient(135deg,#0d9488,#1e3a8a)'])
                <a class="card cat" href="{{ route('public.courses.index', ['subject' => $catName]) }}">
                    <div class="ic" style="background:{{ $categoryStyle[1] }}">
                        <svg class="i"><use href="#{{ $categoryStyle[0] }}"/></svg>
                    </div>
                    <b>{{ $catName }}</b>
                    <small>مشاهده دوره‌ها</small>
                </a>
            @endforeach
        </div>
    </div>
</section>

<div class="band">
    <div class="c">
        <div class="g">
            <div>
                <b>{{ number_format($stats['enrollments']) }}</b>
                <span>{{ $homeContent['stats_labels'][0] }}</span>
            </div>
            <div>
                <b>{{ number_format($stats['lessons']) }}</b>
                <span>{{ $homeContent['stats_labels'][1] }}</span>
            </div>
            <div>
                <b>{{ number_format($stats['teachers']) }}</b>
                <span>{{ $homeContent['stats_labels'][2] }}</span>
            </div>
            <div>
                <b>{{ number_format($stats['courses']) }}</b>
                <span>{{ $homeContent['stats_labels'][3] }}</span>
            </div>
        </div>
    </div>
</div>

<section style="padding:32px 0 64px">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">{{ $homeContent['latest_courses_eyebrow'] }}</span>
                <h2>{{ $homeContent['latest_courses_title'] }}</h2>
                <p>{{ $homeContent['latest_courses_description'] }}</p>
            </div>
            <a class="btn o s" style="color:var(--pri)" href="{{ route('public.courses.index') }}">
                مشاهده همه
            </a>
        </div>
        <div class="g4">
            {{-- دوره‌های واقعی دیتابیس --}}
            @foreach($courses as $course)
                <article class="card cc">
                    @php
                        $courseGradients = [
                            'ریاضی' => ['linear-gradient(135deg,#2563eb,#7c3aed)', 'chart'],
                            'فیزیک' => ['linear-gradient(135deg,#0d9488,#0f766e)', 'rocket'],
                            'شیمی' => ['linear-gradient(135deg,#f97316,#db2777)', 'pen'],
                            'زیست' => ['linear-gradient(135deg,#16a34a,#0d9488)', 'user'],
                            'زبان' => ['linear-gradient(135deg,#0891b2,#2563eb)', 'lang'],
                            'ادبیات' => ['linear-gradient(135deg,#7c3aed,#db2777)', 'pen'],
                        ];
                        $courseStyle = $courseGradients[$course->subject ?? ''] ?? ['linear-gradient(135deg,#0d9488,#1e3a8a)', 'cap'];
                        $courseLessonsCount = (int) $course->sections->sum('lessons_count');
                    @endphp
                    <a class="th" href="{{ route('public.courses.show', $course->slug) }}" style="background:{{ $courseStyle[0] }}" aria-label="مشاهده {{ $course->title }}">
                        <svg class="i"><use href="#{{ $courseStyle[1] }}"/></svg>
                        <span class="bd {{ $course->type === 'free' ? 'bd-free' : '' }}">{{ $course->type === 'free' ? 'رایگان' : ($course->grade ? 'پایه '.$course->grade : ($course->subject ?: 'ویژه')) }}</span>
                    </a>
                    <div class="cb">
                        <h3><a href="{{ route('public.courses.show', $course->slug) }}">{{ $course->title }}</a></h3>
                        @if($course->teacher)
                            <small style="color:var(--mute);margin-bottom:8px;display:block">مدرس: {{ $course->teacher->name }}</small>
                        @endif
                        <div class="mt">
                            <span><svg class="i"><use href="#clock"/></svg>{{ $courseLessonsCount > 0 ? $courseLessonsCount.' درس' : 'دوره آموزشی' }}</span>
                            <span><svg class="i"><use href="#user"/></svg>{{ number_format($course->enrollments_count ?? 0) }} ثبت‌نام فعال</span>
                        </div>
                        <div class="pr">
                            <div>
                                @if($course->type === 'free')
                                    <b style="color:var(--pri)">رایگان</b>
                                @else
                                    <b>{{ number_format((int) $course->price) }}</b><small>تومان</small>
                                @endif
                            </div>
                            <a class="btn s" href="{{ route('public.courses.show', $course->slug) }}">مشاهده</a>
                        </div>
                    </div>
                </article>
            @endforeach


        </div>
    </div>
</section>

<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">{{ $homeContent['popular_courses_eyebrow'] }}</span>
                <h2>{{ $homeContent['popular_courses_title'] }}</h2>
                <p>{{ $homeContent['popular_courses_description'] }}</p>
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


        </div>
    </div>
</section>
{{-- ۴ قدم تا تسلط --}}
<section style="padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">{{ $homeContent['steps_eyebrow'] }}</span>
                <h2>{{ $homeContent['steps_title'] }}</h2>
                <p>{{ $homeContent['steps_description'] }}</p>
            </div>
        </div>
        <div class="g4">
            @foreach($homeContent['steps'] as $index => $step)
                @continue(blank($step['title'] ?? '') && blank($step['body'] ?? ''))
                <div class="card stp">
                    <div class="n">{{ ['۱', '۲', '۳', '۴'][$index] ?? ($index + 1) }}</div>
                    <h3>{{ $step['title'] ?? '' }}</h3>
                    <p>{{ $step['body'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- پنل‌های اختصاصی --}}
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">{{ $homeContent['panels_eyebrow'] ?? 'پنل‌های اختصاصی' }}</span>
                <h2>{{ $homeContent['panels_title'] }}</h2>
                <p>{{ $homeContent['panels_description'] }}</p>
            </div>
        </div>
        @php($panelRoutes = [route('student.dashboard'), route('teacher.dashboard'), route('guardian.dashboard'), route('admin.dashboard')])
        @php($panelIcons = ['cap', 'pen', 'user', 'chart'])
        @php($panelColors = ['linear-gradient(135deg,#0d9488,#0f766e)', 'linear-gradient(135deg,#f97316,#db2777)', 'linear-gradient(135deg,#2563eb,#7c3aed)', 'linear-gradient(135deg,#0891b2,#2563eb)'])
        <div class="g4">
            @foreach($homeContent['panel_cards'] as $index => $panelCard)
                @continue(blank($panelCard['title'] ?? '') && blank($panelCard['body'] ?? ''))
                <a class="card pn" href="{{ $panelRoutes[$index] ?? route('home') }}">
                    <div class="ic" style="background:{{ $panelColors[$index] ?? $panelColors[0] }}">
                        <svg class="i"><use href="#{{ $panelIcons[$index] ?? 'cap' }}"/></svg>
                    </div>
                    <h3>{{ $panelCard['title'] ?? '' }}</h3>
                    <p>{{ $panelCard['body'] ?? '' }}</p>
                    <span class="go">ورود به پنل <svg class="i"><use href="#arrl"/></svg></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- نظرهای تأییدشده از پنل سئو --}}
@if($testimonials->isNotEmpty())
<section style="padding:64px 0">
    <div class="c">
        <div class="sh"><div><span class="eyebrow">{{ $homeContent['testimonials_eyebrow'] }}</span><h2>{{ $homeContent['testimonials_title'] }}</h2></div></div>
        <div class="g3">
            @foreach($testimonials as $testimonial)
                <article class="card ts">
                    <div class="stars" aria-label="{{ $testimonial->rating }} از ۵ ستاره">{{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}</div>
                    <p>«{{ $testimonial->quote }}»</p>
                    <div class="who"><div class="av">{{ mb_substr($testimonial->name, 0, 1) }}</div><div><b>{{ $testimonial->name }}</b>@if($testimonial->role)<small style="color:var(--mute);display:block">{{ $testimonial->role }}</small>@endif</div></div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- مدرسان شیخان --}}
@if($teachers->isNotEmpty())
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">{{ $homeContent['teachers_eyebrow'] }}</span>
                <h2>{{ $homeContent['teachers_title'] }}</h2>
                <p>{{ $homeContent['teachers_description'] }}</p>
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

{{-- مزیت‌های آکادمی؛ متن و کارت‌ها از تنظیمات سئو --}}
<section style="padding:64px 0">
    <div class="c">
        <div class="why">
            <div>
                <span class="eyebrow">{{ $homeContent['benefits_eyebrow'] }}</span>
                <h2>{{ $homeContent['benefits_title'] }}</h2>
                <p>{{ $homeContent['benefits_description'] }}</p>
                <div style="display:flex;gap:12px;flex-wrap:wrap">
                    <a class="btn" href="{{ route('public.courses.index') }}">{{ $homeContent['benefits_primary_label'] }}</a>
                    <a class="btn o" style="color:var(--pri)" href="{{ route('public.contact') }}">{{ $homeContent['benefits_secondary_label'] }}</a>
                </div>
            </div>
            @php($benefitIcons = ['award', 'cam', 'chart', 'user', 'pen', 'help'])
            @php($benefitColors = ['linear-gradient(135deg,#0d9488,#0f766e)', 'linear-gradient(135deg,#f97316,#db2777)', 'linear-gradient(135deg,#2563eb,#7c3aed)', 'linear-gradient(135deg,#0891b2,#2563eb)', 'linear-gradient(135deg,#7c3aed,#db2777)', 'linear-gradient(135deg,#16a34a,#0d9488)'])
            <div class="g3" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">
                @foreach($homeContent['benefits'] as $index => $benefit)
                    @continue(blank($benefit['title'] ?? '') && blank($benefit['body'] ?? ''))
                    <div class="card wf">
                        <div class="ic" style="background:{{ $benefitColors[$index] ?? $benefitColors[0] }}">
                            <svg class="i"><use href="#{{ $benefitIcons[$index] ?? 'cap' }}"/></svg>
                        </div>
                        <h3>{{ $benefit['title'] ?? '' }}</h3>
                        <p>{{ $benefit['body'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@if($articles->isNotEmpty())
{{-- مجله شیخان (مقالات سئو) --}}
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="sh">
            <div>
                <span class="eyebrow">{{ $homeContent['articles_eyebrow'] }}</span>
                <h2>{{ $homeContent['articles_title'] }}</h2>
                <p>{{ $homeContent['articles_description'] }}</p>
            </div>
            <a class="btn o s" style="color:var(--pri)" href="{{ route('seo.articles.index') }}">همه مقالات</a>
        </div>
        <div class="g3">
            @foreach($articles as $article)
                <article class="card cc">
                    @if($article->featured_image_path)
                        <a class="th" href="{{ route('seo.articles.show', $article->slug) }}">
                            <img src="{{ Storage::disk('public')->url($article->featured_image_path) }}" alt="{{ $article->image_alt ?: $article->title }}" style="width:100%;height:100%;object-fit:cover;object-position: {{ $article->image_position ?? '50% 50%' }}" loading="lazy">
                        </a>
                    @else
                        <a class="th" href="{{ route('seo.articles.show', $article->slug) }}" style="background:linear-gradient(135deg,#0d9488,#2563eb)">
                            <svg class="i"><use href="#pen"/></svg>
                            <span class="bd bd-article">مقاله</span>
                        </a>
                    @endif
                    <div class="cb">
                        <h3><a href="{{ route('seo.articles.show', $article->slug) }}">{{ $article->title }}</a></h3>
                        <div class="mt">
                            <span>{{ $article->published_at?->format('Y/m/d') }}</span>
                            <span>{{ max(1, (int) ceil(mb_strlen(strip_tags($article->body ?? ''), 'UTF-8') / 700)) }} دقیقه مطالعه</span>
                        </div>
                        @if($article->excerpt)
                            <p style="color:var(--mute);font-size:13px;line-height:1.8;margin:0 0 14px">{{ Str::limit(strip_tags($article->excerpt), 95) }}</p>
                        @endif
                        <div class="pr"><a class="btn s" href="{{ route('seo.articles.show', $article->slug) }}">ادامه مطلب</a></div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- بنر اقدام به ثبت‌نام (CTA) --}}
<section style="padding:64px 0">
    <div class="c">
        <div class="cta">
            <h2>{{ $homeContent['cta_title'] }}</h2>
            <p>{{ $homeContent['cta_body'] }}</p>
            <a class="btn w" href="{{ route('public.courses.index') }}">
                {{ $homeContent['cta_label'] }} <svg class="i"><use href="#arrl"/></svg>
            </a>
        </div>
    </div>
</section>

{{-- سؤالات متداول (FAQ) --}}
<section style="background:var(--sf);padding:64px 0">
    <div class="c">
        <div class="faq">
            <div>
                <span class="eyebrow">{{ $homeContent['faq_eyebrow'] }}</span>
                <h2 style="font-size:30px;line-height:1.6;margin:8px 0;font-weight:800">{{ $homeContent['faq_title'] }}</h2>
                <p style="color:var(--mute);margin:0 0 20px;line-height:1.9">
                    {{ $homeContent['faq_description'] }}
                </p>
                <a class="btn" href="{{ route('public.contact') }}">{{ $homeContent['faq_cta_label'] }}</a>
            </div>
            <div>
                @foreach($homeContent['faq_items'] as $index => $item)
                    @continue(blank($item['question'] ?? '') && blank($item['answer'] ?? ''))
                    <details @if($index === 0) open @endif>
                        <summary>{{ $item['question'] ?? '' }}</summary>
                        <div class="fa">{{ $item['answer'] ?? '' }}</div>
                    </details>
                @endforeach
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

});
</script>
@endpush
