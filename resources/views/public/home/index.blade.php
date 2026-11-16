
@extends('public.layouts.academy')

@section('title', ($settings->site_name ?? 'آکادمی شیخان').' | آموزش آنلاین')

@section('content')

    @php
        /*
        |--------------------------------------------------------------------------
        | Safe collections and homepage data
        |--------------------------------------------------------------------------
        */

        $bannersCollection = collect($homeBanners ?? []);
        $coursesCollection = collect($courses ?? []);
        $popularCoursesCollection = collect($popularCourses ?? []);
        $categoriesCollection = collect($categories ?? []);
        $teachersCollection = collect($teachers ?? []);
        $testimonialsCollection = collect($testimonials ?? []);
        $articlesCollection = collect($articles ?? []);

        $content = $homeContent ?? [];
        $statsData = $stats ?? [];

        $contentValue = static function (string $key, mixed $default = '') use ($content): mixed {
            return data_get($content, $key, $default);
        };

        $statValue = static function (string $key, mixed $default = 0) use ($statsData): mixed {
            return data_get($statsData, $key, $default);
        };

        $categoryStyles = [
            'ریاضی' => ['chart', 'linear-gradient(135deg,#2563eb,#7c3aed)'],
            'فیزیک' => ['rocket', 'linear-gradient(135deg,#0d9488,#0f766e)'],
            'شیمی' => ['pen', 'linear-gradient(135deg,#f97316,#db2777)'],
            'زیست' => ['user', 'linear-gradient(135deg,#16a34a,#0d9488)'],
            'زبان' => ['lang', 'linear-gradient(135deg,#0891b2,#2563eb)'],
            'ادبیات' => ['brief', 'linear-gradient(135deg,#7c3aed,#db2777)'],
        ];

        $courseStyles = [
            'ریاضی' => ['linear-gradient(135deg,#2563eb,#7c3aed)', 'chart'],
            'فیزیک' => ['linear-gradient(135deg,#0d9488,#0f766e)', 'rocket'],
            'شیمی' => ['linear-gradient(135deg,#f97316,#db2777)', 'pen'],
            'زیست' => ['linear-gradient(135deg,#16a34a,#0d9488)', 'user'],
            'زبان' => ['linear-gradient(135deg,#0891b2,#2563eb)', 'lang'],
            'ادبیات' => ['linear-gradient(135deg,#7c3aed,#db2777)', 'pen'],
        ];

        $defaultCourseStyle = [
            'linear-gradient(135deg,#0d9488,#1e3a8a)',
            'cap',
        ];

        $panelRoutes = [
            'student.dashboard',
            'teacher.dashboard',
            'guardian.dashboard',
            'admin.dashboard',
        ];

        $panelIcons = ['cap', 'pen', 'user', 'chart'];

        $panelColors = [
            'linear-gradient(135deg,#0d9488,#0f766e)',
            'linear-gradient(135deg,#f97316,#db2777)',
            'linear-gradient(135deg,#2563eb,#7c3aed)',
            'linear-gradient(135deg,#0891b2,#2563eb)',
        ];

        $benefitIcons = ['award', 'cam', 'chart', 'user', 'pen', 'help'];

        $benefitColors = [
            'linear-gradient(135deg,#0d9488,#0f766e)',
            'linear-gradient(135deg,#f97316,#db2777)',
            'linear-gradient(135deg,#2563eb,#7c3aed)',
            'linear-gradient(135deg,#0891b2,#2563eb)',
            'linear-gradient(135deg,#7c3aed,#db2777)',
            'linear-gradient(135deg,#16a34a,#0d9488)',
        ];

        $steps = collect($contentValue('steps', []))->filter(
            fn ($step) => filled(data_get($step, 'title')) || filled(data_get($step, 'body'))
        );

        $panelCards = collect($contentValue('panel_cards', []))->filter(
            fn ($panel) => filled(data_get($panel, 'title')) || filled(data_get($panel, 'body'))
        );

        $benefits = collect($contentValue('benefits', []))->filter(
            fn ($benefit) => filled(data_get($benefit, 'title')) || filled(data_get($benefit, 'body'))
        );

        $faqItems = collect($contentValue('faq_items', []))->filter(
            fn ($item) => filled(data_get($item, 'question')) || filled(data_get($item, 'answer'))
        );




    @endphp

    {{-- =========================================================
         HERO / BANNERS
    ========================================================= --}}

    <header class="hero" id="homeHero">

        @if($bannersCollection->count() > 1)
            <button class="arr r" type="button" data-hero-dir="1" aria-label="بعدی">
                <svg class="i"><use href="#arrr"/></svg>
            </button>

            <button class="arr l" type="button" data-hero-dir="-1" aria-label="قبلی">
                <svg class="i"><use href="#arrl"/></svg>
            </button>
        @endif

        @if($bannersCollection->isNotEmpty())

            @foreach($bannersCollection as $index => $banner)

                <div
                        class="sl {{ $index === 0 ? 'on' : '' }}"
                        style="{{ $index % 2 === 1 ? '--a:#f97316;--b:#7c3aed' : '--a:#0d9488;--b:#1e40af' }}"
                >

                    @if(filled($banner->image_path ?? null))
                        <img
                                class="sl-banner-bg"
                                src="{{ Storage::disk('public')->url($banner->image_path) }}"
                                style="object-position:{{ $banner->image_position ?? '50% 50%' }}"
                                alt="{{ $banner->alt_text ?: ($banner->title ?? '') }}"
                        >
                    @endif

                    <div style="position:relative;z-index:2">

                    <span class="bd">
                        {{ $contentValue('hero_badge') }}
                    </span>

                        <h1>{{ $banner->title }}</h1>

                        <p>{{ $contentValue('hero_description') }}</p>

                        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:inherit">

                            @if(filled($banner->target_url ?? null))

                                <a
                                        class="btn"
                                        href="{{ $banner->target_url }}"
                                        @if($banner->open_in_new_tab ?? false)
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @endif
                                >
                                    {{ $contentValue('hero_primary_label') }}
                                    <svg class="i"><use href="#arrl"/></svg>
                                </a>

                            @else

                                <a class="btn" href="{{ route('public.courses.index') }}">
                                    {{ $contentValue('hero_primary_label') }}
                                    <svg class="i"><use href="#arrl"/></svg>
                                </a>

                            @endif

                            <a class="btn o" href="{{ route('public.contact') }}">
                                {{ $contentValue('hero_secondary_label') }}
                            </a>

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

            <div class="sl on">
                <div style="position:relative;z-index:2">

                <span class="bd">
                    {{ $contentValue('hero_badge') }}
                </span>

                    <h1>{{ $settings->site_name ?? 'آکادمی شیخان' }}</h1>

                    <p>{{ $contentValue('hero_description') }}</p>

                    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:inherit">

                        <a class="btn" href="{{ route('public.courses.index') }}">
                            {{ $contentValue('hero_primary_label') }}
                            <svg class="i"><use href="#arrl"/></svg>
                        </a>

                        <a class="btn o" href="{{ route('public.contact') }}">
                            {{ $contentValue('hero_secondary_label') }}
                        </a>

                    </div>
                </div>
            </div>

        @endif

        <div
                class="dots"
                id="heroDots"
                @if($bannersCollection->count() < 2) hidden @endif
        ></div>

    </header>


    {{-- =========================================================
         INTERACTIVE FIVE-STEP QUIZ
    ========================================================= --}}

    <section class="home-quiz-section" id="homeQuiz">

        <div class="c">

            <div class="home-quiz-heading">

                <span class="eyebrow">چالش روز آکادمی شیخان</span>

                <h2>هوشت رو به چالش بکش!</h2>

                <p>
                    پنج مرحله جذاب، پنج سؤال متفاوت؛
                    ببین چند پاسخ صحیح می‌تونی ثبت کنی.
                </p>

            </div>

            <div
                    class="home-quiz-card"
                    data-question-url="{{ url('/home-quiz/question') }}"
                    data-answer-url="{{ route('public.home.quiz.answer') }}"
                    data-csrf="{{ csrf_token() }}"
            >

                {{-- Progress --}}

                <div class="quiz-progress-area">

                    <div class="quiz-progress-top">
                        <span id="quizStepLabel">مرحله ۱ از ۵</span>
                        <span id="quizScoreLabel">امتیاز: ۰</span>
                    </div>

                    <div
                            class="quiz-progress-track"
                            role="progressbar"
                            aria-label="پیشرفت آزمون"
                            aria-valuemin="0"
                            aria-valuemax="5"
                            aria-valuenow="0"
                            id="quizProgress"
                    >
                        <div class="quiz-progress-fill" id="quizProgressFill"></div>
                    </div>

                    <div class="quiz-step-dots" id="quizStepDots">
                        <span class="active">۱</span>
                        <span>۲</span>
                        <span>۳</span>
                        <span>۴</span>
                        <span>۵</span>
                    </div>

                </div>

                {{-- Question --}}

                <div id="quizQuestionArea">

                    <div class="quiz-loading">
                        <span class="quiz-spinner"></span>
                        در حال آماده‌سازی سؤال...
                    </div>

                </div>

                {{-- Feedback --}}

                <div
                        id="quizFeedback"
                        class="quiz-feedback"
                        aria-live="polite"
                        hidden
                ></div>

                {{-- Actions --}}

                <div class="quiz-actions">

                    <button
                            type="button"
                            id="quizNextButton"
                            class="quiz-next-button"
                            hidden
                    >
                        سؤال بعدی
                        <span>←</span>
                    </button>

                    <button
                            type="button"
                            id="quizRetryButton"
                            class="quiz-retry-button"
                            hidden
                    >
                        <span>↻</span>
                        شروع دوباره
                    </button>

                </div>

                {{-- Result --}}

                <div id="quizFinalResult" class="quiz-final-result" hidden>

                    <div class="quiz-result-icon" id="quizResultIcon">
                        🏆
                    </div>

                    <span class="eyebrow">نتیجه آزمون تو</span>

                    <h3 id="quizResultTitle">آفرین!</h3>

                    <div class="quiz-final-score">
                        <strong id="quizFinalScore">۰</strong>
                        <span>از ۵ پاسخ صحیح</span>
                    </div>

                    <p id="quizResultDescription"></p>

                    <button
                            type="button"
                            class="quiz-retry-button"
                            id="quizFinalRetryButton"
                    >
                        ↻ شرکت دوباره در آزمون
                    </button>

                </div>

            </div>

            <div class="quiz-bottom-note">
                <span>✦</span>
                یادگیری با بازی، یک قدم نزدیک‌تر به موفقیت
            </div>

        </div>

    </section>

    <style>
        /* =========================================================
           QUIZ SECTION
        ========================================================= */

        .home-quiz-section {
            padding: 68px 0;
            background:
                    radial-gradient(circle at 10% 20%, rgba(13,148,136,.09), transparent 30%),
                    radial-gradient(circle at 90% 80%, rgba(37,99,235,.08), transparent 30%),
                    var(--sf, #f8fafc);
        }

        .home-quiz-heading {
            text-align: center;
            max-width: 650px;
            margin: 0 auto 30px;
        }

        .home-quiz-heading h2 {
            font-size: clamp(23px, 4vw, 34px);
            font-weight: 900;
            margin: 10px 0;
            color: var(--txt, #172033);
        }

        .home-quiz-heading p {
            color: var(--mute, #64748b);
            font-size: 14px;
            line-height: 2;
            margin: 0;
        }

        .home-quiz-card {
            width: min(100%, 760px);
            margin: auto;
            padding: clamp(20px, 4vw, 38px);
            border-radius: 25px;
            background: #fff;
            border: 1px solid rgba(148,163,184,.2);
            box-shadow: 0 18px 60px rgba(15,23,42,.07);
        }

        /* Progress */

        .quiz-progress-area {
            margin-bottom: 32px;
        }

        .quiz-progress-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .quiz-progress-track {
            height: 9px;
            background: #e9eef5;
            border-radius: 20px;
            overflow: hidden;
        }

        .quiz-progress-fill {
            height: 100%;
            width: 0;
            background: linear-gradient(90deg,#0d9488,#2563eb);
            border-radius: inherit;
            transition: width .4s ease;
        }

        .quiz-step-dots {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            direction: rtl;
        }

        .quiz-step-dots span {
            width: 31px;
            height: 31px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 12px;
            font-weight: 800;
            color: #94a3b8;
            background: #f1f5f9;
            border: 2px solid transparent;
            transition: .2s ease;
        }

        .quiz-step-dots span.active {
            background: #e0f2fe;
            color: #0369a1;
            border-color: #38bdf8;
        }

        .quiz-step-dots span.done {
            background: #dcfce7;
            color: #15803d;
            border-color: #86efac;
        }

        /* Question */

        .quiz-question-category {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 30px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .quiz-question-title {
            font-size: clamp(17px, 3vw, 22px);
            font-weight: 800;
            line-height: 2;
            color: #172033;
            margin: 0 0 22px;
        }

        /* Answers */

        .quiz-options {
            display: grid;
            grid-template-columns: repeat(2,minmax(0,1fr));
            gap: 12px;
        }

        .quiz-option {
            width: 100%;
            min-height: 64px;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            border-radius: 14px;
            padding: 13px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: right;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            transition: .2s ease;
        }

        .quiz-option:hover:not(:disabled) {
            border-color: #0d9488;
            background: #f0fdfa;
            transform: translateY(-2px);
        }

        .quiz-option:disabled {
            cursor: default;
        }

        .quiz-option-key {
            width: 31px;
            height: 31px;
            min-width: 31px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 12px;
            font-weight: 900;
        }

        .quiz-option.correct {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #15803d;
        }

        .quiz-option.correct .quiz-option-key {
            background: #16a34a;
            color: #fff;
        }

        .quiz-option.wrong {
            border-color: #dc2626;
            background: #fef2f2;
            color: #b91c1c;
        }

        .quiz-option.wrong .quiz-option-key {
            background: #dc2626;
            color: #fff;
        }

        /* Feedback */

        .quiz-feedback {
            margin-top: 22px;
            border-radius: 15px;
            padding: 17px 18px;
            line-height: 2;
            font-size: 13px;
            animation: quizFadeIn .25s ease;
        }

        .quiz-feedback.success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .quiz-feedback.error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .quiz-feedback strong {
            display: block;
            margin-bottom: 5px;
            font-size: 15px;
        }

        .quiz-feedback p {
            margin: 0;
        }

        /* Buttons */

        .quiz-actions {
            display: flex;
            justify-content: flex-start;
            margin-top: 22px;
        }

        .quiz-next-button,
        .quiz-retry-button {
            border: 0;
            border-radius: 12px;
            padding: 13px 22px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: .2s ease;
        }

        .quiz-next-button {
            background: #0d9488;
            color: #fff;
        }

        .quiz-next-button:hover {
            background: #0f766e;
            transform: translateY(-2px);
        }

        .quiz-retry-button {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .quiz-retry-button:hover {
            background: #dbeafe;
        }

        .quiz-next-button span {
            margin-right: 10px;
        }

        /* Result */

        .quiz-final-result {
            text-align: center;
            padding: 20px 0 5px;
            animation: quizFadeIn .35s ease;
        }

        .quiz-result-icon {
            width: 85px;
            height: 85px;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: #fef3c7;
            font-size: 38px;
        }

        .quiz-final-result h3 {
            font-size: 25px;
            font-weight: 900;
            margin: 10px 0;
            color: #172033;
        }

        .quiz-final-score {
            margin: 20px auto;
            display: flex;
            align-items: baseline;
            justify-content: center;
            gap: 8px;
            color: #64748b;
        }

        .quiz-final-score strong {
            font-size: 48px;
            color: #0d9488;
            line-height: 1;
        }

        .quiz-final-result p {
            color: #64748b;
            font-size: 14px;
            line-height: 2;
            margin: 0 0 22px;
        }

        .quiz-bottom-note {
            text-align: center;
            margin-top: 22px;
            color: #64748b;
            font-size: 12px;
        }

        .quiz-bottom-note span {
            color: #f59e0b;
            margin-left: 5px;
        }

        .quiz-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            min-height: 150px;
            color: #64748b;
            font-size: 14px;
        }

        .quiz-spinner {
            width: 21px;
            height: 21px;
            border: 3px solid #e2e8f0;
            border-top-color: #0d9488;
            border-radius: 50%;
            animation: quizSpin .7s linear infinite;
        }

        @keyframes quizSpin {
            to { transform: rotate(360deg); }
        }

        @keyframes quizFadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media(max-width:600px) {

            .home-quiz-section {
                padding: 45px 0;
            }

            .home-quiz-card {
                padding: 20px 15px;
                border-radius: 19px;
            }

            .quiz-options {
                grid-template-columns: 1fr;
                gap: 9px;
            }

            .quiz-option {
                min-height: 56px;
            }

            .quiz-step-dots span {
                width: 27px;
                height: 27px;
            }

            .quiz-question-title {
                font-size: 17px;
            }

        }
    </style>

    {{-- =========================================================
         CATEGORIES
    ========================================================= --}}

    <section style="padding:64px 0">
        <div class="c">

            <div class="sh">
                <div>
                <span class="eyebrow">
                    {{ $contentValue('categories_eyebrow') }}
                </span>

                    <h2>{{ $contentValue('categories_title') }}</h2>
                </div>
            </div>

            <div class="g6">

                @if($categoriesCollection->isNotEmpty())
                    @foreach($categoriesCollection as $category)

                    @php
                        $categoryName = is_string($category)
                            ? $category
                            : ($category->name ?? '');

                        $categoryStyle = $categoryStyles[$categoryName] ?? [
                            'cap',
                            'linear-gradient(135deg,#0d9488,#1e3a8a)',
                        ];
                    @endphp

                    <a
                            class="card cat"
                            href="{{ route('public.courses.index', ['subject' => $categoryName]) }}"
                    >
                        <div class="ic" style="background:{{ $categoryStyle[1] }}">
                            <svg class="i">
                                <use href="#{{ $categoryStyle[0] }}"/>
                            </svg>
                        </div>

                        <b>{{ $categoryName }}</b>

                        <small>مشاهده دوره‌ها</small>
                    </a>

                @endforeach
                @else

                    <div class="card" style="grid-column:1/-1;padding:24px;text-align:center">
                        <p style="margin:0;color:var(--mute)">
                            هنوز دسته‌بندی‌ای برای نمایش ثبت نشده است.
                        </p>
                    </div>

                @endif

            </div>
        </div>
    </section>


    {{-- =========================================================
         STATISTICS
    ========================================================= --}}

    <div class="band">
        <div class="c">

            <div class="g">

                <div>
                    <b>{{ number_format((int) $statValue('enrollments')) }}</b>
                    <span>{{ $contentValue('stats_labels.0') }}</span>
                </div>

                <div>
                    <b>{{ number_format((int) $statValue('lessons')) }}</b>
                    <span>{{ $contentValue('stats_labels.1') }}</span>
                </div>

                <div>
                    <b>{{ number_format((int) $statValue('teachers')) }}</b>
                    <span>{{ $contentValue('stats_labels.2') }}</span>
                </div>

                <div>
                    <b>{{ number_format((int) $statValue('courses')) }}</b>
                    <span>{{ $contentValue('stats_labels.3') }}</span>
                </div>

            </div>
        </div>
    </div>


    {{-- =========================================================
         LATEST COURSES
    ========================================================= --}}

    <section style="padding:32px 0 64px">
        <div class="c">

            <div class="sh">

                <div>
                <span class="eyebrow">
                    {{ $contentValue('latest_courses_eyebrow') }}
                </span>

                    <h2>{{ $contentValue('latest_courses_title') }}</h2>

                    <p>{{ $contentValue('latest_courses_description') }}</p>
                </div>

                <a class="btn o s" style="color:var(--pri)" href="{{ route('public.courses.index') }}">
                    مشاهده همه
                </a>

            </div>

            <div class="g4">

                @if($coursesCollection->isNotEmpty())
                    @foreach($coursesCollection as $homeCourse)

                    @php
                        $subject = $homeCourse->subject ?? '';

                        $courseStyle = $courseStyles[$subject] ?? $defaultCourseStyle;

                        $lessonsCount = (int) (
                            $homeCourse->sections?->sum('lessons_count') ?? 0
                        );

                        $isFree = ($homeCourse->type ?? '') === 'free';
                    @endphp

                    <article class="card cc">

                        <a
                                class="th"
                                href="{{ route('public.courses.show', $homeCourse->slug) }}"
                                style="background:{{ $courseStyle[0] }}"
                                aria-label="مشاهده {{ $homeCourse->title }}"
                        >

                            <svg class="i">
                                <use href="#{{ $courseStyle[1] }}"/>
                            </svg>

                            <span class="bd {{ $isFree ? 'bd-free' : '' }}">
                            {{ $isFree ? 'رایگان' : (($homeCourse->grade ?? null) ? 'پایه '.$homeCourse->grade : ($subject ?: 'ویژه')) }}
                        </span>

                        </a>

                        <div class="cb">

                            <h3>
                                <a href="{{ route('public.courses.show', $homeCourse->slug) }}">
                                    {{ $homeCourse->title }}
                                </a>
                            </h3>

                            @if($homeCourse->teacher)

                                <small style="color:var(--mute);margin-bottom:8px;display:block">
                                    مدرس: {{ $homeCourse->teacher->name }}
                                </small>

                            @endif

                            <div class="mt">

                            <span>
                                <svg class="i"><use href="#clock"/></svg>
                                {{ $lessonsCount > 0 ? $lessonsCount.' درس' : 'دوره آموزشی' }}
                            </span>

                                <span>
                                <svg class="i"><use href="#user"/></svg>
                                {{ number_format((int) ($homeCourse->enrollments_count ?? 0)) }}
                                ثبت‌نام فعال
                            </span>

                            </div>

                            <div class="pr">

                                <div>

                                    @if($isFree)

                                        <b style="color:var(--pri)">رایگان</b>

                                    @else

                                        <b>{{ number_format((int) $homeCourse->price) }}</b>
                                        <small>تومان</small>

                                    @endif

                                </div>

                                <a class="btn s" href="{{ route('public.courses.show', $homeCourse->slug) }}">
                                    مشاهده
                                </a>

                            </div>
                        </div>

                    </article>

                @endforeach
                @else

                    <div class="card" style="grid-column:1/-1;padding:24px;text-align:center">

                        <p style="margin:0;color:var(--mute)">
                            در حال حاضر دوره‌ای برای نمایش منتشر نشده است.
                        </p>

                        <a
                                class="btn s"
                                style="margin-top:12px"
                                href="{{ route('public.courses.index') }}"
                        >
                            مشاهده فهرست دوره‌ها
                        </a>

                    </div>

                @endif

            </div>
        </div>
    </section>


    {{-- =========================================================
         POPULAR COURSES
    ========================================================= --}}

    <section style="background:var(--sf);padding:64px 0">
        <div class="c">

            <div class="sh">

                <div>
                <span class="eyebrow">
                    {{ $contentValue('popular_courses_eyebrow') }}
                </span>

                    <h2>{{ $contentValue('popular_courses_title') }}</h2>

                    <p>{{ $contentValue('popular_courses_description') }}</p>
                </div>

                <a
                        class="btn o s"
                        style="color:var(--pri)"
                        href="{{ route('public.courses.index', ['sort' => 'popular']) }}"
                >
                    مشاهده پرفروش‌ترین‌ها
                </a>

            </div>

            <div class="g3">

                @if($popularCoursesCollection->isNotEmpty())
                    @foreach($popularCoursesCollection as $index => $course)

                    @php
                        $subject = $course->subject ?? '';

                        $popularStyle = $courseStyles[$subject] ?? $defaultCourseStyle;

                        $isFree = ($course->type ?? '') === 'free';
                    @endphp

                    <a class="card pop" href="{{ route('public.courses.show', $course->slug) }}">

                        <span class="n">{{ $index + 1 }}</span>

                        <div class="th" style="background:{{ $popularStyle[0] }}">
                            <svg class="i">
                                <use href="#{{ $popularStyle[1] }}"/>
                            </svg>
                        </div>

                        <div style="flex:1">

                            <h3>{{ $course->title }}</h3>

                            <div class="mt" style="margin-bottom:4px">

                            <span style="color:#f59e0b">
                                <svg class="i"><use href="#star"/></svg>
                            </span>

                                <span>
                                <svg class="i"><use href="#user"/></svg>
                                {{ number_format((int) ($course->enrollments_count ?? 0)) }}
                                دانشجو
                            </span>

                            </div>

                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px">

                            <span style="font-size:13px;color:var(--mute)">
                                {{ $course->teacher?->name ?? 'آموزشگاه شیخان' }}
                            </span>

                                <span style="font-weight:700;color:var(--pri);font-size:14px">

                                {{ $isFree ? 'رایگان' : number_format((int) $course->price).' ت' }}

                            </span>

                            </div>
                        </div>

                    </a>

                @endforeach
                @else

                    <div class="card" style="grid-column:1/-1;padding:24px;text-align:center">
                        <p style="margin:0;color:var(--mute)">
                            هنوز دوره محبوبی برای نمایش وجود ندارد.
                        </p>
                    </div>

                @endif

            </div>
        </div>
    </section>


    {{-- =========================================================
         LEARNING STEPS
    ========================================================= --}}

    <section style="padding:64px 0">
        <div class="c">

            <div class="sh">
                <div>
                    <span class="eyebrow">{{ $contentValue('steps_eyebrow') }}</span>
                    <h2>{{ $contentValue('steps_title') }}</h2>
                    <p>{{ $contentValue('steps_description') }}</p>
                </div>
            </div>

            <div class="g4">

                @if($steps->isNotEmpty())
                    @foreach($steps as $index => $step)

                    <div class="card stp">

                        <div class="n">
                            {{ ['۱', '۲', '۳', '۴'][$index] ?? ($index + 1) }}
                        </div>

                        <h3>{{ data_get($step, 'title', '') }}</h3>

                        <p>{{ data_get($step, 'body', '') }}</p>

                    </div>

                @endforeach
                @else

                    <div class="card" style="grid-column:1/-1;padding:20px">
                        <p style="margin:0;color:var(--mute)">
                            اطلاعات مراحل یادگیری هنوز تنظیم نشده است.
                        </p>
                    </div>

                @endif

            </div>
        </div>
    </section>


    {{-- =========================================================
         DASHBOARD PANELS
    ========================================================= --}}

    <section style="background:var(--sf);padding:64px 0">
        <div class="c">

            <div class="sh">
                <div>

                <span class="eyebrow">
                    {{ $contentValue('panels_eyebrow', 'پنل‌های اختصاصی') }}
                </span>

                    <h2>{{ $contentValue('panels_title') }}</h2>

                    <p>{{ $contentValue('panels_description') }}</p>

                </div>
            </div>

            <div class="g4">

                @if($panelCards->isNotEmpty())
                    @foreach($panelCards as $index => $panelCard)

                    @php
                        $panelRoute = $panelRoutes[$index] ?? null;
                        $panelUrl = $panelRoute ? route($panelRoute) : route('home');
                    @endphp

                    <a class="card pn" href="{{ $panelUrl }}">

                        <div
                                class="ic"
                                style="background:{{ $panelColors[$index] ?? $panelColors[0] }}"
                        >
                            <svg class="i">
                                <use href="#{{ $panelIcons[$index] ?? 'cap' }}"/>
                            </svg>
                        </div>

                        <h3>{{ data_get($panelCard, 'title', '') }}</h3>

                        <p>{{ data_get($panelCard, 'body', '') }}</p>

                        <span class="go">
                        ورود به پنل
                        <svg class="i"><use href="#arrl"/></svg>
                    </span>

                    </a>

                @endforeach
                @else

                    <div class="card" style="grid-column:1/-1;padding:20px">
                        <p style="margin:0;color:var(--mute)">
                            پنل‌های اختصاصی هنوز تنظیم نشده‌اند.
                        </p>
                    </div>

                @endif

            </div>
        </div>
    </section>


    {{-- =========================================================
         TESTIMONIALS
    ========================================================= --}}

    @if($testimonialsCollection->isNotEmpty())

        <section style="padding:64px 0">
            <div class="c">

                <div class="sh">
                    <div>
                    <span class="eyebrow">
                        {{ $contentValue('testimonials_eyebrow') }}
                    </span>

                        <h2>{{ $contentValue('testimonials_title') }}</h2>
                    </div>
                </div>

                <div class="g3">

                    @foreach($testimonialsCollection as $testimonial)

                        @php
                            $rating = min(5, max(0, (int) ($testimonial->rating ?? 5)));
                        @endphp

                        <article class="card ts">

                            <div class="stars" aria-label="{{ $rating }} از ۵ ستاره">
                                {{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }}
                            </div>

                            <p>«{{ $testimonial->quote }}»</p>

                            <div class="who">

                                <div class="av">
                                    {{ mb_substr($testimonial->name ?? '', 0, 1) }}
                                </div>

                                <div>
                                    <b>{{ $testimonial->name }}</b>

                                    @if(filled($testimonial->role ?? null))
                                        <small style="color:var(--mute);display:block">
                                            {{ $testimonial->role }}
                                        </small>
                                    @endif
                                </div>

                            </div>

                        </article>

                    @endforeach

                </div>
            </div>
        </section>

    @endif


    {{-- =========================================================
         TEACHERS
    ========================================================= --}}

    @if($teachersCollection->isNotEmpty())

        <section style="background:var(--sf);padding:64px 0">
            <div class="c">

                <div class="sh">

                    <div>
                    <span class="eyebrow">
                        {{ $contentValue('teachers_eyebrow') }}
                    </span>

                        <h2>{{ $contentValue('teachers_title') }}</h2>

                        <p>{{ $contentValue('teachers_description') }}</p>
                    </div>

                    <a
                            class="btn o s"
                            style="color:var(--pri)"
                            href="{{ route('public.teachers.index') }}"
                    >
                        همه مدرسان
                    </a>

                </div>

                <div class="g4">

                    @foreach($teachersCollection as $teacher)

                        <div class="card tcard">

                            <div
                                    class="av"
                                    style="background:linear-gradient(135deg,var(--pri),var(--acc))"
                            >
                                {{ mb_substr($teacher->name ?? '', 0, 1) }}
                            </div>

                            <h3>{{ $teacher->name }}</h3>

                            <span class="bd">
                            مدرس {{ $teacher->subject ?: 'تخصصی' }}
                        </span>

                            <div class="mt">

                            <span style="color:#f59e0b">
                                <svg class="i"><use href="#star"/></svg>
                            </span>

                                <span>
                                <svg class="i"><use href="#cam"/></svg>
                                {{ $teacher->published_courses_count ?? 1 }}
                                دوره
                            </span>

                            </div>

                            <a
                                    class="btn o s"
                                    style="color:var(--pri);width:100%"
                                    href="{{ route('public.teachers.show', $teacher->id) }}"
                            >
                                مشاهده پروفایل
                            </a>

                        </div>

                    @endforeach

                </div>
            </div>
        </section>

    @endif


    {{-- =========================================================
         BENEFITS
    ========================================================= --}}

    <section style="padding:64px 0">
        <div class="c">

            <div class="why">

                <div>

                <span class="eyebrow">
                    {{ $contentValue('benefits_eyebrow') }}
                </span>

                    <h2>{{ $contentValue('benefits_title') }}</h2>

                    <p>{{ $contentValue('benefits_description') }}</p>

                    <div style="display:flex;gap:12px;flex-wrap:wrap">

                        <a class="btn" href="{{ route('public.courses.index') }}">
                            {{ $contentValue('benefits_primary_label') }}
                        </a>

                        <a
                                class="btn o"
                                style="color:var(--pri)"
                                href="{{ route('public.contact') }}"
                        >
                            {{ $contentValue('benefits_secondary_label') }}
                        </a>

                    </div>

                </div>

                <div class="g3" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr))">

                    @if($benefits->isNotEmpty())
                    @foreach($benefits as $index => $benefit)

                        <div class="card wf">

                            <div
                                    class="ic"
                                    style="background:{{ $benefitColors[$index] ?? $benefitColors[0] }}"
                            >
                                <svg class="i">
                                    <use href="#{{ $benefitIcons[$index] ?? 'cap' }}"/>
                                </svg>
                            </div>

                            <h3>{{ data_get($benefit, 'title', '') }}</h3>

                            <p>{{ data_get($benefit, 'body', '') }}</p>

                        </div>

                    @endforeach
                @else

                        <div class="card">
                            <p style="margin:0;color:var(--mute)">
                                اطلاعات مزایای آکادمی هنوز تنظیم نشده است.
                            </p>
                        </div>

                    @endif

                </div>

            </div>
        </div>
    </section>


    {{-- =========================================================
         ARTICLES
    ========================================================= --}}

    @if($articlesCollection->isNotEmpty())

        <section style="background:var(--sf);padding:64px 0">
            <div class="c">

                <div class="sh">

                    <div>
                    <span class="eyebrow">
                        {{ $contentValue('articles_eyebrow') }}
                    </span>

                        <h2>{{ $contentValue('articles_title') }}</h2>

                        <p>{{ $contentValue('articles_description') }}</p>
                    </div>

                    <a
                            class="btn o s"
                            style="color:var(--pri)"
                            href="{{ route('seo.articles.index') }}"
                    >
                        همه مقالات
                    </a>

                </div>

                <div class="g3">

                    @foreach($articlesCollection as $article)

                        <article class="card cc">

                            @if(filled($article->featured_image_path ?? null))

                                <a
                                        class="th"
                                        href="{{ route('seo.articles.show', $article->slug) }}"
                                >

                                    <img
                                            src="{{ Storage::disk('public')->url($article->featured_image_path) }}"
                                            alt="{{ $article->image_alt ?: $article->title }}"
                                            style="width:100%;height:100%;object-fit:cover;object-position:{{ $article->image_position ?? '50% 50%' }}"
                                            loading="lazy"
                                    >

                                </a>

                            @else

                                <a
                                        class="th"
                                        href="{{ route('seo.articles.show', $article->slug) }}"
                                        style="background:linear-gradient(135deg,#0d9488,#2563eb)"
                                >

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

                                <span>
                                    {{ $article->published_at?->format('Y/m/d') }}
                                </span>

                                    <span>
                                    {{ max(1, (int) ceil(mb_strlen(strip_tags($article->body ?? ''), 'UTF-8') / 700)) }}
                                    دقیقه مطالعه
                                </span>

                                </div>

                                @if(filled($article->excerpt ?? null))

                                    <p style="color:var(--mute);font-size:13px;line-height:1.8;margin:0 0 14px">
                                        {{ Str::limit(strip_tags($article->excerpt), 95) }}
                                    </p>

                                @endif

                                <div class="pr">

                                    <a
                                            class="btn s"
                                            href="{{ route('seo.articles.show', $article->slug) }}"
                                    >
                                        ادامه مطلب
                                    </a>

                                </div>

                            </div>

                        </article>

                    @endforeach

                </div>
            </div>
        </section>

    @endif


    {{-- =========================================================
         CALL TO ACTION
    ========================================================= --}}

    <section style="padding:64px 0">
        <div class="c">

            <div class="cta">

                <h2>{{ $contentValue('cta_title') }}</h2>

                <p>{{ $contentValue('cta_body') }}</p>

                <a class="btn w" href="{{ route('public.courses.index') }}">
                    {{ $contentValue('cta_label') }}
                    <svg class="i"><use href="#arrl"/></svg>
                </a>

            </div>

        </div>
    </section>


    {{-- =========================================================
         FAQ
    ========================================================= --}}

    <section style="background:var(--sf);padding:64px 0">
        <div class="c">

            <div class="faq">

                <div>

                <span class="eyebrow">
                    {{ $contentValue('faq_eyebrow') }}
                </span>

                    <h2 style="font-size:30px;line-height:1.6;margin:8px 0;font-weight:800">
                        {{ $contentValue('faq_title') }}
                    </h2>

                    <p style="color:var(--mute);margin:0 0 20px;line-height:1.9">
                        {{ $contentValue('faq_description') }}
                    </p>

                    <a class="btn" href="{{ route('public.contact') }}">
                        {{ $contentValue('faq_cta_label') }}
                    </a>

                </div>

                <div>

                    @if($faqItems->isNotEmpty())
                    @foreach($faqItems as $index => $item)

                        <details @if($index === 0) open @endif>

                            <summary>
                                {{ data_get($item, 'question', '') }}
                            </summary>

                            <div class="fa">
                                {{ data_get($item, 'answer', '') }}
                            </div>

                        </details>

                    @endforeach
                @else

                        <div class="card" style="padding:20px">
                            <p style="margin:0;color:var(--mute)">
                                سؤالات متداول هنوز تنظیم نشده‌اند.
                            </p>
                        </div>

                    @endif

                </div>

            </div>
        </div>
    </section>

@endsection


{{-- =========================================================
     HERO SLIDER JAVASCRIPT
========================================================= --}}


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const quiz = document.getElementById('homeQuiz');

    if (!quiz) return;

    const questionArea = document.getElementById('quizQuestionArea');
    const feedbackArea = document.getElementById('quizFeedback');

    const nextButton = document.getElementById('quizNextButton');

    const finalResult = document.getElementById('quizFinalResult');
    const finalRetryButton = document.getElementById('quizFinalRetryButton');

    const stepLabel = document.getElementById('quizStepLabel');
    const scoreLabel = document.getElementById('quizScoreLabel');

    const progress = document.getElementById('quizProgress');
    const progressFill = document.getElementById('quizProgressFill');

    const dots = Array.from(
        document.querySelectorAll('#quizStepDots span')
    );

    const questionUrl = quiz.dataset.questionUrl;
    const answerUrl = quiz.dataset.answerUrl;
    const csrf = quiz.dataset.csrf;

    const total = 5;

    let currentStep = 1;
    let score = 0;
    let locked = false;
    let loading = false;

    const persianDigits = value =>
        String(value).replace(/\d/g, digit =>
            '۰۱۲۳۴۵۶۷۸۹'[digit]
        );

    function updateProgress() {

        stepLabel.textContent =
            `مرحله ${persianDigits(currentStep)} از ${persianDigits(total)}`;

        scoreLabel.textContent =
            `امتیاز: ${persianDigits(score)}`;

        progressFill.style.width =
            `${((currentStep - 1) / total) * 100}%`;

        progress.setAttribute(
            'aria-valuenow',
            String(currentStep - 1)
        );

        dots.forEach((dot, index) => {

            dot.classList.toggle(
                'active',
                index === currentStep - 1
            );

            dot.classList.toggle(
                'done',
                index < currentStep - 1
            );

        });

    }

    async function loadQuestion(step) {

        loading = true;
        locked = true;

        currentStep = step;

        updateProgress();

        feedbackArea.hidden = true;
        nextButton.hidden = true;
        finalResult.hidden = true;

        questionArea.hidden = false;

        questionArea.innerHTML = `
            <div class="quiz-loading">
                <span class="quiz-spinner"></span>
                در حال دریافت سؤال...
            </div>
        `;

        try {

            const response = await fetch(
                `${questionUrl}/${step}`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message || 'دریافت سؤال ناموفق بود.'
                );
            }

            renderQuestion(result.question);

            loading = false;
            locked = false;

        } catch (error) {

            questionArea.innerHTML = `
                <div class="quiz-feedback error">
                    <strong>خطا در دریافت سؤال</strong>
                    <p>${escapeHtml(error.message)}</p>
                    <button type="button" class="quiz-retry-button" id="quizLoadRetry">
                        تلاش مجدد
                    </button>
                </div>
            `;

            const loadRetry = document.getElementById('quizLoadRetry');

            loadRetry.addEventListener('click', () => {
                loadQuestion(currentStep);
            });

            loading = false;
            locked = true;

        }

    }

    function renderQuestion(question) {

        const options = Object.entries(question.options);

        questionArea.innerHTML = `
            <div class="quiz-question-category">
                ✦ ${escapeHtml(question.category)}
            </div>

            <h3 class="quiz-question-title">
                ${escapeHtml(question.question)}
            </h3>

            <div class="quiz-options" id="quizOptions">

                ${options.map(([key, label]) => `
                    <button
                        type="button"
                        class="quiz-option"
                        data-answer="${escapeHtml(key)}"
                    >
                        <span class="quiz-option-key">
                            ${escapeHtml(key)}
                        </span>

                        <span>
                            ${escapeHtml(label)}
                        </span>
                    </button>
                `).join('')}

            </div>
        `;

        questionArea
            .querySelectorAll('.quiz-option')
            .forEach(button => {

                button.addEventListener('click', function () {

                    submitAnswer(
                        this.dataset.answer,
                        this
                    );

                });

            });

    }

    async function submitAnswer(answer, selectedButton) {

        if (locked || loading) return;

        locked = true;

        const buttons = Array.from(
            questionArea.querySelectorAll('.quiz-option')
        );

        buttons.forEach(button => {
            button.disabled = true;
        });

        selectedButton.classList.add('selected');

        selectedButton.innerHTML += `
            <span class="quiz-spinner"></span>
        `;

        try {

            const response = await fetch(answerUrl, {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },

                body: JSON.stringify({
                    step: currentStep,
                    answer: answer
                })

            });

            const result = await response.json();

            if (!response.ok || !result.success) {

                throw new Error(
                    result.message || 'بررسی پاسخ ناموفق بود.'
                );

            }

            selectedButton.querySelector('.quiz-spinner')?.remove();

            if (result.correct) {

                score++;

                selectedButton.classList.remove('selected');
                selectedButton.classList.add('correct');

                feedbackArea.className =
                    'quiz-feedback success';

                feedbackArea.innerHTML = `
                    <strong>✓ آفرین! پاسخ درست بود.</strong>
                    <p>${escapeHtml(result.explanation)}</p>
                `;

            } else {

                selectedButton.classList.remove('selected');
                selectedButton.classList.add('wrong');

                const correctButton = buttons.find(
                    button =>
                        button.dataset.answer === result.correct_answer
                );

                if (correctButton) {
                    correctButton.classList.add('correct');
                }

                feedbackArea.className =
                    'quiz-feedback error';

                feedbackArea.innerHTML = `
                    <strong>✕ این پاسخ درست نبود.</strong>

                    <p>
                        پاسخ صحیح:
                        <b>${escapeHtml(result.correct_label)}</b>
                    </p>

                    <p style="margin-top:8px">
                        ${escapeHtml(result.explanation)}
                    </p>
                `;

            }

            feedbackArea.hidden = false;

            scoreLabel.textContent =
                `امتیاز: ${persianDigits(score)}`;

            progressFill.style.width =
                `${(currentStep / total) * 100}%`;

            progress.setAttribute(
                'aria-valuenow',
                String(currentStep)
            );

            dots[currentStep - 1].classList.add('done');
            dots[currentStep - 1].classList.remove('active');

            if (result.is_last) {

                nextButton.hidden = true;

                setTimeout(() => {
                    showFinalResult();
                }, 1800);

            } else {

                nextButton.hidden = false;

                nextButton.textContent =
                    'مرحله بعدی ←';

                nextButton.focus();

            }

        } catch (error) {

            selectedButton.querySelector('.quiz-spinner')?.remove();

            buttons.forEach(button => {
                button.disabled = false;
                button.classList.remove('selected');
            });

            selectedButton.classList.remove('selected');

            feedbackArea.className =
                'quiz-feedback error';

            feedbackArea.innerHTML = `
                <strong>خطا در بررسی پاسخ</strong>
                <p>${escapeHtml(error.message)}</p>
                <p>لطفاً دوباره پاسخ خود را انتخاب کن.</p>
            `;

            feedbackArea.hidden = false;

            locked = false;

        }

    }

    function showFinalResult() {

        questionArea.hidden = true;
        feedbackArea.hidden = true;
        nextButton.hidden = true;

        finalResult.hidden = false;

        document.getElementById('quizFinalScore').textContent =
            persianDigits(score);

        let title = '';
        let description = '';
        let icon = '🏆';

        if (score === 5) {

            title = 'فوق‌العاده بود!';
            description = 'تمام مراحل را درست پاسخ دادی. تو یک قهرمان واقعی هستی!';
            icon = '🏆';

        } else if (score >= 4) {

            title = 'عالی عمل کردی!';
            description = 'عملکردت بسیار خوب بود. فقط کمی تمرین بیشتر لازم داری.';
            icon = '🌟';

        } else if (score >= 2) {

            title = 'خوب پیش رفتی!';
            description = 'چند سؤال را درست پاسخ دادی. با تمرین بیشتر بهتر می‌شوی.';
            icon = '💪';

        } else {

            title = 'شروع خوبی بود!';
            description = 'مهم این است که یاد می‌گیری. دوباره امتحان کن و پیشرفتت را ببین.';
            icon = '🚀';

        }

        document.getElementById('quizResultTitle').textContent =
            title;

        document.getElementById('quizResultDescription').textContent =
            description;

        document.getElementById('quizResultIcon').textContent =
            icon;

        stepLabel.textContent = 'آزمون تمام شد';

        progressFill.style.width = '100%';

        progress.setAttribute('aria-valuenow', '5');

        dots.forEach(dot => {
            dot.classList.remove('active');
            dot.classList.add('done');
        });

    }

    function restartQuiz() {

        currentStep = 1;
        score = 0;
        locked = false;
        loading = false;

        updateProgress();

        loadQuestion(1);

    }

    function escapeHtml(value) {

        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        })[character]);

    }

    nextButton.addEventListener('click', function () {

        if (locked && !nextButton.hidden) {
            loadQuestion(currentStep + 1);
        }

    });

    finalRetryButton.addEventListener('click', restartQuiz);

    restartQuiz();

});
</script>
@endpush

@push('scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const hero = document.getElementById('homeHero');

            if (!hero) {
                return;
            }

            const slides = Array.from(hero.querySelectorAll('.sl'));
            const dotsContainer = document.getElementById('heroDots');

            if (slides.length <= 1 || !dotsContainer) {
                return;
            }

            let currentIndex = 0;
            let timer = null;

            dotsContainer.innerHTML = '';

            const dots = slides.map((slide, index) => {

                const dot = document.createElement('i');

                if (index === 0) {
                    dot.classList.add('on');
                }

                dot.setAttribute('role', 'button');
                dot.setAttribute('tabindex', '0');
                dot.setAttribute('aria-label', 'نمایش اسلاید ' + (index + 1));

                dot.addEventListener('click', function () {
                    goToSlide(index);
                    resetAutoplay();
                });

                dot.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        goToSlide(index);
                        resetAutoplay();
                    }
                });

                dotsContainer.appendChild(dot);

                return dot;
            });

            function goToSlide(index) {

                currentIndex = (index + slides.length) % slides.length;

                slides.forEach(function (slide, slideIndex) {
                    slide.classList.toggle('on', slideIndex === currentIndex);
                });

                dots.forEach(function (dot, dotIndex) {
                    dot.classList.toggle('on', dotIndex === currentIndex);
                });
            }

            hero.querySelectorAll('[data-hero-dir]').forEach(function (button) {

                button.addEventListener('click', function () {

                    const direction = parseInt(
                        button.getAttribute('data-hero-dir'),
                        10
                    ) || 1;

                    goToSlide(currentIndex + direction);

                    resetAutoplay();
                });

            });

            function startAutoplay() {

                if (timer !== null) {
                    clearInterval(timer);
                }

                timer = setInterval(function () {
                    goToSlide(currentIndex + 1);
                }, 6500);

            }

            function resetAutoplay() {
                startAutoplay();
            }

            hero.addEventListener('mouseenter', function () {
                if (timer !== null) {
                    clearInterval(timer);
                    timer = null;
                }
            });

            hero.addEventListener('mouseleave', function () {
                if (timer === null) {
                    startAutoplay();
                }
            });

            document.addEventListener('visibilitychange', function () {

                if (document.hidden) {

                    if (timer !== null) {
                        clearInterval(timer);
                        timer = null;
                    }

                } else if (timer === null) {

                    startAutoplay();

                }

            });

            startAutoplay();

        });
    </script>

@endpush
