
@extends('public.layouts.academy')

@section('title', $settings->home_sections['home_meta_title'] ?? (($settings->site_name ?? 'آکادمی شیخان').' | آموزش آنلاین'))
@section('description', $settings->home_sections['home_meta_description'] ?? $settings->default_description)

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
        $recommendedCoursesCollection = collect($recommendedCourses ?? $popularCourses ?? []);
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

    @if($bannersCollection->first()?->image_path)
        @section('seo-image', Storage::disk('public')->url($bannersCollection->first()->image_path))
        @section('seo-image-alt', $bannersCollection->first()->alt_text ?: $bannersCollection->first()->title)
    @endif

    {{-- =========================================================
         HERO / SLIDER (Inspired by modern card design)
    ========================================================= --}}

    <section class="hero-wrap">
        <div class="c">
            <header class="hero" id="homeHero" aria-label="اسلایدر ویژه آکادمی">

                @if($bannersCollection->count() > 1)
                    <button class="arr prev" type="button" data-hero-dir="-1" aria-label="اسلاید قبلی">
                        <svg class="i"><use href="#arrr"/></svg>
                    </button>

                    <button class="arr next" type="button" data-hero-dir="1" aria-label="اسلاید بعدی">
                        <svg class="i"><use href="#arrl"/></svg>
                    </button>
                @endif

                @if($bannersCollection->isNotEmpty())

                    @foreach($bannersCollection as $index => $banner)
                        @php
                            $slideBg = $banner->bg_color ?: ($index % 2 === 1 ? 'linear-gradient(135deg, #4c1d95, #7c3aed)' : 'linear-gradient(135deg, #5b21b6, #8b5cf6)');
                            $isFullBg = ($banner->layout_type ?? 'side_image') === 'full_background';
                            $hasImage = filled($banner->image_path ?? null);
                            $badgeText = $banner->badge ?: $contentValue('hero_badge');
                            $subtitleText = $banner->subtitle ?: $contentValue('hero_description');
                            $primaryBtnText = $banner->button_text ?: $contentValue('hero_primary_label', 'مشاهده دوره‌ها');
                            $primaryBtnUrl = $banner->target_url ?: route('public.courses.index');
                            $secondaryBtnText = $banner->secondary_button_text ?: $contentValue('hero_secondary_label', 'مشاوره رایگان');
                            $secondaryBtnUrl = $banner->secondary_button_url ?: route('public.contact');
                        @endphp

                        <div
                            class="sl {{ $index === 0 ? 'on' : '' }} {{ $isFullBg ? 'layout-full-bg' : 'layout-card' }} {{ $hasImage ? 'has-banner-image' : '' }}"
                            style="--banner-bg: {{ $slideBg }}; {{ $banner->text_color ? '--banner-text:' . $banner->text_color . ';' : '' }}"
                            data-slide-index="{{ $index }}"
                        >

                            @if($isFullBg && $hasImage)
                                <img
                                    class="sl-banner-bg"
                                    src="{{ Storage::disk('public')->url($banner->image_path) }}"
                                    style="object-position:{{ $banner->image_position ?? '50% 50%' }}"
                                    alt="{{ $banner->alt_text ?: ($banner->title ?? '') }}"
                                >
                            @endif

                            <div class="sl-content">
                                @if(filled($badgeText))
                                    <span class="sl-badge">
                                        <svg class="i" style="width:14px;height:14px"><use href="#award"/></svg>
                                        {{ $badgeText }}
                                    </span>
                                @endif

                                <h1>{{ $banner->title }}</h1>

                                @if(filled($subtitleText))
                                    <p>{{ $subtitleText }}</p>
                                @endif

                                <div class="sl-actions">
                                    <a
                                        class="sl-btn-primary"
                                        href="{{ $primaryBtnUrl }}"
                                        @if($banner->open_in_new_tab ?? false)
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @endif
                                    >
                                        <span>{{ $primaryBtnText }}</span>
                                        <svg class="i"><use href="#arrl"/></svg>
                                    </a>

                                    @if(filled($secondaryBtnText))
                                        <a class="sl-btn-secondary" href="{{ $secondaryBtnUrl }}">
                                            <span>{{ $secondaryBtnText }}</span>
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if(!$isFullBg)
                                <div class="sl-media">
                                    @if($hasImage)
                                        <img
                                            class="sl-banner-bg"
                                            src="{{ Storage::disk('public')->url($banner->image_path) }}"
                                            style="object-position:{{ $banner->image_position ?? '50% 50%' }}"
                                            alt="{{ $banner->alt_text ?: ($banner->title ?? '') }}"
                                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                        >
                                    @else
                                        <div class="vis">
                                            <i class="m"><svg class="i"><use href="#cap"/></svg></i>
                                            <i class="t1"><svg class="i"><use href="#rocket"/></svg></i>
                                            <i class="t2"><svg class="i"><use href="#chart"/></svg></i>
                                        </div>
                                    @endif
                                </div>
                            @endif

                        </div>

                    @endforeach

                @else

                    <div
                        class="sl on layout-card"
                        style="--banner-bg: linear-gradient(135deg, #4c1d95, #7c3aed);"
                        data-slide-index="0"
                    >
                        <div class="sl-content">
                            @if(filled($contentValue('hero_badge')))
                                <span class="sl-badge">
                                    <svg class="i" style="width:14px;height:14px"><use href="#award"/></svg>
                                    {{ $contentValue('hero_badge') }}
                                </span>
                            @endif

                            <h1>{{ $settings->site_name ?? 'آموزشگاه شیخان' }}</h1>

                            <p>{{ $contentValue('hero_description', 'آموزش هدفمند و مرحله‌به‌مرحله با اساتید مجرب و دسترسی همیشگی به محتوای آموزشی.') }}</p>

                            <div class="sl-actions">
                                <a class="sl-btn-primary" href="{{ route('public.courses.index') }}">
                                    <span>{{ $contentValue('hero_primary_label', 'مشاهده دوره‌ها') }}</span>
                                    <svg class="i"><use href="#arrl"/></svg>
                                </a>

                                <a class="sl-btn-secondary" href="{{ route('public.contact') }}">
                                    <span>{{ $contentValue('hero_secondary_label', 'مشاوره رایگان') }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="sl-media">
                            <div class="vis">
                                <i class="m"><svg class="i"><use href="#cap"/></svg></i>
                                <i class="t1"><svg class="i"><use href="#rocket"/></svg></i>
                                <i class="t2"><svg class="i"><use href="#chart"/></svg></i>
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
        </div>
    </section>


    {{-- =========================================================
         DIRECTOR / FOUNDER MESSAGE (Inspired by Gold-Accent Card)
    ========================================================= --}}

    @php
        $academyName = $settings->site_name ?? 'آکادمی شیخان';
        $directorName = $contentValue('director_name') ?: ($settings->organization_name ?: 'مدیریت ' . $academyName);
        $directorRole = $contentValue('director_role') ?: 'مدیر و موسس آموزشگاه';
        $directorPortrait = !empty($settings->organization_logo_path)
            ? Storage::disk('public')->url($settings->organization_logo_path)
            : null;
        $directorMessage = $contentValue('director_message') ?: (
            'آموزش هدفمند را دوست داریم؛ نه فقط به خاطر کتاب‌ها، بلکه به خاطر ساختن مسیر آینده دانش‌آموزان. بچه‌های عزیز، اگر بخواهیم در مسیر یادگیری موفق شویم، باید فرهنگ تلاش، سخت‌کوشی، هدف‌گذاری، تمرکز و برنامه‌ریزی اصولی را یاد بگیریم. در ' . $academyName . ' کنار شما هستیم تا با آموزش مفهومی، تمرین مستمر و همراهی اساتید مجرب، یادگیری عمیق و لذت‌بخش را تجربه کنید و با اعتمادبه‌نفس کامل به اهداف تحصیلی خود برسید.'
        );
    @endphp

    <section class="director-section" id="directorMessage">
        <div class="c">
            <div class="director-grid">
                <div class="director-card-wrap">
                    <span class="director-orb top" aria-hidden="true"></span>
                    <span class="director-orb bottom" aria-hidden="true"></span>

                    <div class="director-card">
                        <span class="director-watermark" aria-hidden="true">«</span>
                        <h2 class="director-name">{{ $directorName }}</h2>
                        <span class="director-role">{{ $directorRole }}</span>

                        <div class="director-portrait">
                            @if($directorPortrait)
                                <img src="{{ $directorPortrait }}" alt="{{ $directorName }}" loading="lazy">
                            @else
                                <span class="director-portrait-monogram" aria-hidden="true">
                                    {{ mb_substr($directorName, 0, 1) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <p class="director-copy">{{ $directorMessage }}</p>
                </div>
            </div>
        </div>
    </section>


    {{-- =========================================================
         INTERACTIVE HOMEPAGE QUIZ
    ========================================================= --}}

    @if(($homeQuizTotal ?? 0) > 0)
    <section
            class="home-quiz-section"
            id="homeQuiz"
            data-question-url="{{ url('/home-quiz/question') }}"
            data-answer-url="{{ route('public.home.quiz.answer') }}"
            data-csrf="{{ csrf_token() }}"
            data-total="{{ $homeQuizTotal ?? 0 }}"
    >

        <div class="c">

            <div class="home-quiz-heading">

                <span class="eyebrow">چالش روز آکادمی شیخان</span>

                <h2>هوشت رو به چالش بکش!</h2>

                <p>
                    چند سؤال جذاب و معمای متفاوت؛
                    ببین چند پاسخ صحیح می‌تونی ثبت کنی.
                </p>

            </div>

            <div class="home-quiz-card">

                {{-- Progress --}}

                <div class="quiz-progress-area">

                    <div class="quiz-progress-top">
                        <span id="quizStepLabel">مرحله ۱ از {{ $homeQuizTotal ?? 0 }}</span>
                        <span id="quizScoreLabel">امتیاز: ۰</span>
                    </div>

                    <div
                            class="quiz-progress-track"
                            role="progressbar"
                            aria-label="پیشرفت آزمون"
                            aria-valuemin="0"
                            aria-valuemax="{{ $homeQuizTotal ?? 0 }}"
                            aria-valuenow="0"
                            id="quizProgress"
                    >
                        <div class="quiz-progress-fill" id="quizProgressFill"></div>
                    </div>

                    <div class="quiz-step-dots" id="quizStepDots"></div>

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
                        <span>از {{ $homeQuizTotal ?? 0 }} پاسخ صحیح</span>
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
    @endif

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
            color: var(--ink, #172033);
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
         RECOMMENDED COURSES (Inspired by clean 3-col cards + tabs)
    ========================================================= --}}

    @if($recommendedCoursesCollection->isNotEmpty())
        <section class="rec-section" id="recommendedCourses">
            <div class="c">
                <div class="rec-head">
                    <div class="rec-head-top">
                        <div class="rec-title-wrap">
                            <span class="rec-accent-bar" aria-hidden="true"></span>
                            <h2>{{ $contentValue('recommended_courses_title', 'دوره‌های پیشنهادی آکادمی شیخان') }}</h2>
                        </div>

                        <a class="rec-all-link" href="{{ route('public.courses.index') }}">
                            مشاهده همه دوره‌ها
                            <svg class="i"><use href="#arrl"/></svg>
                        </a>
                    </div>
                </div>

                @php
                    $recCoverPalettes = [
                        'linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #0d9488 100%)',
                        'linear-gradient(135deg, #111827 0%, #312e81 60%, #6d28d9 100%)',
                        'linear-gradient(135deg, #090d16 0%, #0f4c5c 60%, #0284c7 100%)',
                        'linear-gradient(135deg, #18181b 0%, #4c1d95 60%, #db2777 100%)',
                        'linear-gradient(135deg, #0f172a 0%, #065f46 60%, #0d9488 100%)',
                        'linear-gradient(135deg, #1e1b4b 0%, #1d4ed8 60%, #0ea5e9 100%)',
                    ];
                @endphp

                @foreach(['popular' => $recommendedCoursesCollection->take(3)] as $tabKey => $tabCourses)
                    <div class="rec-panel {{ $tabKey === 'popular' ? 'is-active' : '' }}" data-rec-panel="{{ $tabKey }}">
                        <div class="rec-grid">
                            @foreach($tabCourses as $cIndex => $recCourse)
                                @php
                                    $recSubject = $recCourse->subject ?? '';
                                    $recStyle = $courseStyles[$recSubject] ?? $defaultCourseStyle;
                                    $recCover = $recCourse->coverImageUrl ?: $recCourse->teaserPosterUrl;
                                    $recLessonsCount = (int) ($recCourse->sections?->sum('lessons_count') ?? 0);
                                    $recIsFree = ($recCourse->type ?? '') === 'free' || (int) $recCourse->price === 0;
                                    $recPalette = $recCoverPalettes[$cIndex % count($recCoverPalettes)];
                                    $recBadgeLabel = $recIsFree
                                        ? 'رایگان'
                                        : (($recCourse->grade ?? null) ? 'پایه '.$recCourse->grade : ($recSubject ?: 'ویژه'));
                                    $recMetaLabel = $recLessonsCount > 0
                                        ? $recLessonsCount.' درس'
                                        : ($recCourse->season ? (\App\Models\Course::SEASONS[$recCourse->season] ?? $recCourse->season) : 'دوره جامع');
                                @endphp
                                <a class="rec-card" href="{{ route('public.courses.show', $recCourse->slug) }}">
                                    <div class="rec-thumb" style="background:{{ $recPalette }}">
                                        @if($recCover)
                                            <img
                                                src="{{ $recCover }}"
                                                alt="تصویر دوره {{ $recCourse->title }}"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        @else
                                            <div class="rec-thumb-fallback">
                                                <span class="rec-thumb-fallback-icon">
                                                    <svg class="i"><use href="#{{ $recStyle[1] }}"/></svg>
                                                </span>
                                                <span class="rec-thumb-fallback-title">{{ \Illuminate\Support\Str::limit($recCourse->title, 48) }}</span>
                                                @if($recSubject)
                                                    <span class="rec-thumb-fallback-sub">{{ $recSubject }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        <span class="rec-badge {{ $recIsFree ? 'is-free' : '' }}">
                                            {{ $recBadgeLabel }}
                                        </span>
                                    </div>

                                    <div class="rec-body">
                                        <h3 class="rec-title" title="{{ $recCourse->title }}">{{ $recCourse->title }}</h3>

                                        <div class="rec-teacher">
                                            <svg class="i"><use href="#user"/></svg>
                                            <span>{{ $recCourse->teacher?->name ?? 'اساتیـد آکادمی' }}</span>
                                        </div>

                                        <div class="rec-foot">
                                            <span class="rec-price">
                                                @if($recIsFree)
                                                    رایگان
                                                @else
                                                    {{ number_format((int) $recCourse->price) }}
                                                    <small>تومان</small>
                                                @endif
                                            </span>

                                            <span class="rec-meta-info">
                                                <svg class="i"><use href="#clock"/></svg>
                                                <span>{{ $recMetaLabel }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

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
         LATEST COURSES (Unified 3-Col Card Design)
    ========================================================= --}}

    <section class="rec-section" style="padding:32px 0 64px">
        <div class="c">

            <div class="rec-head">
                <div class="rec-head-top">
                    <div class="rec-title-wrap">
                        <span class="rec-accent-bar" aria-hidden="true"></span>
                        <div>
                            <h2>{{ $contentValue('latest_courses_title', 'دوره‌های تازه') }}</h2>
                            @if(filled($contentValue('latest_courses_description')))
                                <p style="margin:4px 0 0;color:var(--mute);font-size:13px">{{ $contentValue('latest_courses_description') }}</p>
                            @endif
                        </div>
                    </div>

                    <a class="rec-all-link" href="{{ route('public.courses.index') }}">
                        مشاهده همه
                        <svg class="i"><use href="#arrl"/></svg>
                    </a>
                </div>
            </div>

            <div class="rec-grid">

                @if($coursesCollection->isNotEmpty())
                    @php
                        $latestCoverPalettes = [
                            'linear-gradient(135deg, #0f172a 0%, #1e3a8a 60%, #0d9488 100%)',
                            'linear-gradient(135deg, #111827 0%, #312e81 60%, #6d28d9 100%)',
                            'linear-gradient(135deg, #090d16 0%, #0f4c5c 60%, #0284c7 100%)',
                        ];
                    @endphp
                    @foreach($coursesCollection->take(3) as $lIndex => $homeCourse)
                        @php
                            $subject = $homeCourse->subject ?? '';
                            $courseStyle = $courseStyles[$subject] ?? $defaultCourseStyle;
                            $courseImageUrl = $homeCourse->coverImageUrl ?: $homeCourse->teaserPosterUrl;
                            $lessonsCount = (int) ($homeCourse->sections?->sum('lessons_count') ?? 0);
                            $isFree = ($homeCourse->type ?? '') === 'free' || (int) $homeCourse->price === 0;
                            $latestPalette = $latestCoverPalettes[$lIndex % count($latestCoverPalettes)];
                            $latestBadgeLabel = $isFree
                                ? 'رایگان'
                                : (($homeCourse->grade ?? null) ? 'پایه '.$homeCourse->grade : ($subject ?: 'ویژه'));
                            $latestMetaLabel = $lessonsCount > 0
                                ? $lessonsCount.' درس'
                                : ($homeCourse->season ? (\App\Models\Course::SEASONS[$homeCourse->season] ?? $homeCourse->season) : 'دوره جامع');
                        @endphp
                        <a class="rec-card" href="{{ route('public.courses.show', $homeCourse->slug) }}">
                            <div class="rec-thumb" style="background:{{ $latestPalette }}">
                                @if($courseImageUrl)
                                    <img
                                        src="{{ $courseImageUrl }}"
                                        alt="تصویر دوره {{ $homeCourse->title }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                @else
                                    <div class="rec-thumb-fallback">
                                        <span class="rec-thumb-fallback-icon">
                                            <svg class="i"><use href="#{{ $courseStyle[1] }}"/></svg>
                                        </span>
                                        <span class="rec-thumb-fallback-title">{{ \Illuminate\Support\Str::limit($homeCourse->title, 48) }}</span>
                                        @if($subject)
                                            <span class="rec-thumb-fallback-sub">{{ $subject }}</span>
                                        @endif
                                    </div>
                                @endif

                                <span class="rec-badge {{ $isFree ? 'is-free' : '' }}">
                                    {{ $latestBadgeLabel }}
                                </span>
                            </div>

                            <div class="rec-body">
                                <h3 class="rec-title" title="{{ $homeCourse->title }}">{{ $homeCourse->title }}</h3>

                                <div class="rec-teacher">
                                    <svg class="i"><use href="#user"/></svg>
                                    <span>{{ $homeCourse->teacher?->name ?? 'اساتید آکادمی' }}</span>
                                </div>

                                <div class="rec-foot">
                                    <span class="rec-price">
                                        @if($isFree)
                                            رایگان
                                        @else
                                            {{ number_format((int) $homeCourse->price) }}
                                            <small>تومان</small>
                                        @endif
                                    </span>

                                    <span class="rec-meta-info">
                                        <svg class="i"><use href="#clock"/></svg>
                                        <span>{{ $latestMetaLabel }}</span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                @else

                    <div class="card" style="grid-column:1/-1;padding:24px;text-align:center">
                        <p style="margin:0;color:var(--mute)">
                            در حال حاضر دوره‌ای برای نمایش منتشر نشده است.
                        </p>
                        <a class="btn s" style="margin-top:12px" href="{{ route('public.courses.index') }}">
                            مشاهده فهرست دوره‌ها
                        </a>
                    </div>

                @endif

            </div>
        </div>
    </section>


    {{-- =========================================================
         STATISTICS
    ========================================================= --}}


    {{-- Registration announcement replaces the static learning-steps section. --}}
    @include('public.partials.registration-ticket')


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
         TESTIMONIALS / STUDENT STORIES (5-Col Vertical Reel Cards)
    ========================================================= --}}

    @if($testimonialsCollection->isNotEmpty())

        <section class="story-section" id="homeStories">
            <div class="c">
                <div class="rec-head">
                    <div class="rec-head-top">
                        <div class="rec-title-wrap">
                            <span class="rec-accent-bar" aria-hidden="true"></span>
                            <h2>{{ $contentValue('testimonials_title', 'تجربه و موفقیت دانش‌آموزان آکادمی شیخان') }}</h2>
                        </div>

                        <a class="rec-all-link" href="{{ route('public.courses.index') }}">
                            شروع یادگیری در آکادمی
                            <svg class="i"><use href="#arrl"/></svg>
                        </a>
                    </div>
                </div>

                @php
                    $storyPalettes = [
                        'linear-gradient(160deg, #312e81 0%, #4c1d95 55%, #0f172a 100%)',
                        'linear-gradient(160deg, #0f172a 0%, #1e3a8a 55%, #0d9488 100%)',
                        'linear-gradient(160deg, #3b0764 0%, #831843 55%, #1e1b4b 100%)',
                        'linear-gradient(160deg, #064e3b 0%, #0f766e 55%, #0f172a 100%)',
                        'linear-gradient(160deg, #7c2d12 0%, #9a3412 55%, #1e1b4b 100%)',
                    ];
                    $featuredStories = $testimonialsCollection->take(5);
                    $latestStories = $testimonialsCollection->sortByDesc('id')->values()->take(5);
                @endphp

                @foreach(['featured' => $featuredStories, 'latest' => $latestStories] as $sTabKey => $sTabList)
                    <div class="rec-panel {{ $sTabKey === 'featured' ? 'is-active' : '' }}" data-story-panel="{{ $sTabKey }}">
                        <div class="story-grid">
                            @foreach($sTabList as $sIndex => $testimonial)
                                @php
                                    $rating = min(5, max(1, (int) ($testimonial->rating ?? 5)));
                                    $sPalette = $storyPalettes[$sIndex % count($storyPalettes)];
                                @endphp
                                <button
                                    type="button"
                                    class="story-card"
                                    style="--story-bg: {{ $sPalette }}"
                                    data-story-open
                                    data-story-name="{{ $testimonial->name }}"
                                    data-story-role="{{ $testimonial->role ?? 'دانش‌آموز آکادمی شیخان' }}"
                                    data-story-quote="{{ $testimonial->quote }}"
                                    data-story-rating="{{ $rating }}"
                                    aria-label="مشاهده تجربه {{ $testimonial->name }}"
                                >
                                    <div class="story-top">
                                        <span class="story-brand">{{ $settings->site_name ?? 'آکادمی شیخان' }}</span>
                                        <span class="story-stars" aria-label="{{ $rating }} از ۵">{{ str_repeat('★', $rating) }}</span>
                                    </div>

                                    <div class="story-quote-body">
                                        <span class="story-quote-mark" aria-hidden="true">«</span>
                                        <p class="story-excerpt">{{ $testimonial->quote }}</p>
                                    </div>

                                    <div class="story-bottom">
                                        <span class="story-avatar-badge" aria-hidden="true">{{ mb_substr($testimonial->name ?? 'ش', 0, 1) }}</span>
                                        <div>
                                            <span class="story-name">{{ $testimonial->name }}</span>
                                            @if(filled($testimonial->role ?? null))
                                                <span class="story-role">{{ $testimonial->role }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <dialog class="story-modal" id="storyModal" aria-labelledby="storyModalName">
            <div class="story-modal-head">
                <div>
                    <b id="storyModalName" style="display:block;font-size:17px"></b>
                    <small id="storyModalRole" style="opacity:.88;font-size:12px"></small>
                </div>
                <button type="button" class="story-modal-close" id="storyModalClose" aria-label="بستن">×</button>
            </div>
            <div class="story-modal-body">
                <div id="storyModalStars" style="color:#f59e0b;font-size:15px;margin-bottom:10px"></div>
                <p class="story-modal-quote" id="storyModalQuote"></p>
                <a class="btn" href="{{ route('public.courses.index') }}" style="width:100%">
                    مشاهده دوره‌های آموزشی آکادمی
                    <svg class="i"><use href="#arrl"/></svg>
                </a>
            </div>
        </dialog>

    @endif


    {{-- =========================================================
         TEACHERS (Inspired by Mentor Cards Showcase)
    ========================================================= --}}

    @if($teachersCollection->isNotEmpty())

        <section class="tmentor-section" id="homeTeachers">
            <div class="c">
                <div class="rec-head">
                    <div class="rec-head-top">
                        <div class="rec-title-wrap">
                            <span class="rec-accent-bar" style="background:linear-gradient(180deg, #be123c, #f43f5e)" aria-hidden="true"></span>
                            <h2>{{ $contentValue('teachers_title', 'اساتید برتر آکادمی شیخان') }}</h2>
                        </div>

                        <a class="rec-all-link" style="color:#be123c" href="{{ route('public.teachers.index') }}">
                            مشاهده همه مدرسان
                            <svg class="i"><use href="#arrl"/></svg>
                        </a>
                    </div>
                </div>

                @php
                    $allTeachers = $teachersCollection->take(4);
                    $topTeachers = $teachersCollection->sortByDesc('published_courses_count')->values()->take(4);
                @endphp

                @foreach(['all' => $allTeachers, 'top' => $topTeachers] as $tTabKey => $tTabList)
                    <div class="rec-panel {{ $tTabKey === 'all' ? 'is-active' : '' }}" data-tmentor-panel="{{ $tTabKey }}">
                        <div class="tmentor-grid">
                            @foreach($tTabList as $teacher)
                                @php
                                    $teacherDisplayName = str_starts_with(trim((string) $teacher->name), 'استاد')
                                        ? $teacher->name
                                        : 'استاد ' . $teacher->name;
                                @endphp
                                <a class="tmentor-card" href="{{ route('public.teachers.show', $teacher->id) }}">
                                    <span class="tmentor-corner-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="width:100%;height:100%">
                                            <path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96.44 2.5 2.5 0 0 1-2.96-3.08 3 3 0 0 1-.34-5.58 2.5 2.5 0 0 1 1.32-4.24 2.5 2.5 0 0 1 1.98-3A2.5 2.5 0 0 1 9.5 2Z"/>
                                            <path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96.44 2.5 2.5 0 0 0 2.96-3.08 3 3 0 0 0 .34-5.58 2.5 2.5 0 0 0-1.32-4.24 2.5 2.5 0 0 0-1.98-3A2.5 2.5 0 0 0 14.5 2Z"/>
                                        </svg>
                                    </span>

                                    <div class="tmentor-frame">
                                        @if($teacher->avatarUrl())
                                            <img
                                                src="{{ $teacher->avatarUrl() }}"
                                                alt="تصویر {{ $teacherDisplayName }}"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        @else
                                            <span class="tmentor-monogram" aria-hidden="true">
                                                {{ mb_substr($teacher->name ?? 'م', 0, 1) }}
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="tmentor-name">{{ $teacherDisplayName }}</h3>

                                    <span class="tmentor-sub">
                                        {{ $teacher->subject ? 'مدرس '.$teacher->subject : 'مدرس تخصصی آکادمی' }}
                                        @if(($teacher->published_courses_count ?? 0) > 0)
                                            · {{ $teacher->published_courses_count }} دوره
                                        @endif
                                    </span>

                                    <span class="tmentor-cta">
                                        <span>دیدن رزومه</span>
                                        <svg class="i"><use href="#arrl"/></svg>
                                    </span>

                                    <span class="tmentor-bottom-bar" aria-hidden="true"></span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
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

                        @php
                            $articleImagePath = $article->featured_image_path ?: $article->og_image_path;
                        @endphp

                        <article class="card cc">

                            @if(filled($articleImagePath))

                                <a
                                        class="th"
                                        href="{{ route('seo.articles.show', $article->slug) }}"
                                >

                                    <img
                                            src="{{ Storage::disk('public')->url($articleImagePath) }}"
                                            alt="{{ $article->image_alt ?: $article->title }}"
                                            style="width:100%;height:100%;object-fit:cover;object-position:{{ $article->image_position ?? '50% 50%' }}"
                                            loading="lazy"
                                            decoding="async"
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


    {{-- =========================================================
         FAMILY / STUDENT GUIDANCE (Final CTA)
    ========================================================= --}}

    <section aria-labelledby="home-guidance-title" style="padding:36px 0 64px">
        <div class="c">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:22px;align-items:center;padding:clamp(24px,4vw,42px);border-radius:24px;background:linear-gradient(125deg,#102b55,#155e75 62%,#0f766e);color:#fff;box-shadow:0 18px 45px rgba(15,45,80,.16)">
                <div>
                    <span style="display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border:1px solid rgba(255,255,255,.28);border-radius:999px;background:rgba(255,255,255,.1);font-size:12px;font-weight:800">راهنمای شروع</span>
                    <h2 id="home-guidance-title" style="font-size:clamp(22px,3vw,30px);line-height:1.7;margin:12px 0 8px;color:#fff">برای انتخاب دوره مناسب مطمئن نیستید؟</h2>
                    <p style="margin:0;max-width:620px;color:rgba(255,255,255,.86);line-height:2">
                        اگر به‌عنوان والد یا دانش‌آموز درباره انتخاب درس، سطح کلاس، ثبت‌نام یا شیوه آموزش سؤال دارید، پیام بگذارید تا راهنمایی‌تان کنیم.
                    </p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:flex-start">
                    <a class="btn w" href="{{ route('public.contact') }}" style="min-height:46px;display:inline-flex;align-items:center;justify-content:center">
                        دریافت راهنمایی و مشاوره
                        <svg class="i" aria-hidden="true"><use href="#arrl"/></svg>
                    </a>
                    <a href="{{ route('public.courses.index') }}" style="min-height:46px;display:inline-flex;align-items:center;justify-content:center;padding:0 18px;border:1px solid rgba(255,255,255,.55);border-radius:12px;color:#fff;font-weight:800;text-decoration:none">
                        دیدن دوره‌ها
                    </a>
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

    const dots = [];
    const dotsContainer = document.getElementById('quizStepDots');

    const questionUrl = quiz.dataset.questionUrl;
    const answerUrl = quiz.dataset.answerUrl;
    const csrf = quiz.dataset.csrf;

    const total = Number.parseInt(quiz.dataset.total || '0', 10);
    for (let index = 0; index < total; index++) {
        const dot = document.createElement('span');
        dot.textContent = String(index + 1).replace(/[0-9]/g, digit => '۰۱۲۳۴۵۶۷۸۹'[digit]);
        dotsContainer.appendChild(dot);
        dots.push(dot);
    }

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

    async function readQuizJson(response) {
        let result = null;

        try {
            result = await response.json();
        } catch (error) {
            // A proxy or server error may return an HTML page instead of JSON.
        }

        if (!response.ok || !result || !result.success) {
            const message = result?.message
                || (response.status === 419
                    ? 'نشست شما منقضی شده است؛ صفحه را تازه‌سازی کنید.'
                    : 'ارتباط با سرویس معما با خطا مواجه شد. کد خطا: ' + response.status);
            throw new Error(message);
        }

        return result;
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

            const result = await readQuizJson(response);

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

        if (question.type === 'riddle') {
            questionArea.innerHTML = `
                <div class="quiz-question-category">✦ ${escapeHtml(question.category)}</div>
                <h3 class="quiz-question-title">${escapeHtml(question.question)}</h3>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <input id="quizRiddleAnswer" class="quiz-option" type="text" maxlength="255" placeholder="پاسخ معما را بنویس..." style="flex:1;min-width:220px">
                    <button type="button" class="quiz-option" id="quizRiddleSubmit">ثبت پاسخ</button>
                </div>
            `;
            document.getElementById('quizRiddleSubmit').addEventListener('click', function () {
                submitAnswer(document.getElementById('quizRiddleAnswer').value, this);
            });
            return;
        }

        const options = Object.entries(question.options || {});

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

            const result = await readQuizJson(response);

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

        if (score === total) {

            title = 'فوق‌العاده بود!';
            description = 'تمام مراحل را درست پاسخ دادی. تو یک قهرمان واقعی هستی!';
            icon = '🏆';

        } else if (score >= Math.ceil(total * 0.8)) {

            title = 'عالی عمل کردی!';
            description = 'عملکردت بسیار خوب بود. فقط کمی تمرین بیشتر لازم داری.';
            icon = '🌟';

        } else if (score >= Math.ceil(total * 0.4)) {

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

        progress.setAttribute('aria-valuenow', String(total));

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
            if (!hero) return;

            const slides = Array.from(hero.querySelectorAll('.sl'));
            const dotsContainer = document.getElementById('heroDots');

            if (slides.length <= 1) return;

            let currentIndex = 0;
            let timer = null;

            if (dotsContainer) {
                dotsContainer.innerHTML = '';
            }

            const dots = dotsContainer ? slides.map((slide, index) => {
                const dot = document.createElement('i');
                if (index === 0) dot.classList.add('on');
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
            }) : [];

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
                button.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const direction = parseInt(button.getAttribute('data-hero-dir'), 10) || 1;
                    goToSlide(currentIndex + direction);
                    resetAutoplay();
                });
            });

            function startAutoplay() {
                if (timer !== null) clearInterval(timer);
                timer = setInterval(function () {
                    goToSlide(currentIndex + 1);
                }, 5500);
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
                if (timer === null) startAutoplay();
            });

            // Touch Swipe Support for mobile devices
            let touchStartX = 0;
            let touchStartY = 0;
            let touchEndX = 0;

            hero.addEventListener('touchstart', function (e) {
                if (!e.touches || e.touches.length === 0) return;
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
            }, { passive: true });

            hero.addEventListener('touchend', function (e) {
                if (!e.changedTouches || e.changedTouches.length === 0) return;
                touchEndX = e.changedTouches[0].clientX;
                const touchEndY = e.changedTouches[0].clientY;
                const diffX = touchEndX - touchStartX;
                const diffY = touchEndY - touchStartY;

                // Only consider horizontal swipes (ignore vertical scrolling)
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 40) {
                    if (diffX < 0) {
                        // Swiped left (in RTL, forward)
                        goToSlide(currentIndex + 1);
                    } else {
                        // Swiped right (in RTL, back)
                        goToSlide(currentIndex - 1);
                    }
                    resetAutoplay();
                }
            }, { passive: true });

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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const recSection = document.getElementById('recommendedCourses');
            if (!recSection) return;

            const tabs = Array.from(recSection.querySelectorAll('[data-rec-tab]'));
            const panels = Array.from(recSection.querySelectorAll('[data-rec-panel]'));

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const target = tab.getAttribute('data-rec-tab');

                    tabs.forEach(function (t) {
                        const active = t.getAttribute('data-rec-tab') === target;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });

                    panels.forEach(function (p) {
                        p.classList.toggle('is-active', p.getAttribute('data-rec-panel') === target);
                    });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const teacherSection = document.getElementById('homeTeachers');
            if (!teacherSection) return;

            const tTabs = Array.from(teacherSection.querySelectorAll('[data-tmentor-tab]'));
            const tPanels = Array.from(teacherSection.querySelectorAll('[data-tmentor-panel]'));

            tTabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const target = tab.getAttribute('data-tmentor-tab');

                    tTabs.forEach(function (t) {
                        const active = t.getAttribute('data-tmentor-tab') === target;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });

                    tPanels.forEach(function (p) {
                        p.classList.toggle('is-active', p.getAttribute('data-tmentor-panel') === target);
                    });
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const storySection = document.getElementById('homeStories');
            if (!storySection) return;

            const sTabs = Array.from(storySection.querySelectorAll('[data-story-tab]'));
            const sPanels = Array.from(storySection.querySelectorAll('[data-story-panel]'));

            sTabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    const target = tab.getAttribute('data-story-tab');

                    sTabs.forEach(function (t) {
                        const active = t.getAttribute('data-story-tab') === target;
                        t.classList.toggle('is-active', active);
                        t.setAttribute('aria-selected', active ? 'true' : 'false');
                    });

                    sPanels.forEach(function (p) {
                        p.classList.toggle('is-active', p.getAttribute('data-story-panel') === target);
                    });
                });
            });

            const modal = document.getElementById('storyModal');
            const modalName = document.getElementById('storyModalName');
            const modalRole = document.getElementById('storyModalRole');
            const modalStars = document.getElementById('storyModalStars');
            const modalQuote = document.getElementById('storyModalQuote');
            const modalClose = document.getElementById('storyModalClose');

            if (modal && modalName && modalRole && modalStars && modalQuote) {
                storySection.querySelectorAll('[data-story-open]').forEach(function (card) {
                    card.addEventListener('click', function () {
                        const rating = parseInt(card.getAttribute('data-story-rating'), 10) || 5;
                        modalName.textContent = card.getAttribute('data-story-name') || '';
                        modalRole.textContent = card.getAttribute('data-story-role') || '';
                        modalStars.textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating);
                        modalQuote.textContent = '«' + (card.getAttribute('data-story-quote') || '') + '»';
                        modal.showModal();
                    });
                });

                if (modalClose) {
                    modalClose.addEventListener('click', function () {
                        modal.close();
                    });
                }

                modal.addEventListener('click', function (e) {
                    if (e.target === modal) modal.close();
                });
            }
        });
    </script>

@endpush
