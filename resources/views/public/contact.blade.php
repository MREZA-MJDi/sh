@extends('public.layouts.academy')
@section('title', ($settings->home_sections['contact_page_title'] ?? 'تماس با ما') . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'آکادمی شیخان'))
@section('description', $settings->home_sections['contact_page_description'] ?? $settings->default_description)

@section('content')
@php
    $homeSections = is_array($settings->home_sections ?? null) ? $settings->home_sections : [];
    $pageTitle = $homeSections['contact_page_title'] ?? 'تماس با ما';
    $pageDescription = $homeSections['contact_page_description'] ?? 'پیامت را ثبت کن تا تیم پشتیبانی و مشاوره آکادمی آن را بررسی و پیگیری کند.';
    $supportTitle = $homeSections['contact_support_title'] ?? 'پشتیبانی و مشاوره';
    $supportBody = $homeSections['contact_support_body'] ?? 'پیام‌های ثبت‌شده در سامانه ذخیره می‌شوند و کارشناسان آکادمی در کوتاه‌ترین زمان با شما تماس می‌گیرند.';
    $topics = collect($homeSections['contact_topics'] ?? ['پشتیبانی دوره', 'مسائل مالی', 'همکاری', 'سایر'])->filter()->values();
    $faqTitle = $homeSections['contact_faq_title'] ?? 'سؤالات متداول';
    $faqItems = collect($homeSections['contact_faq_items'] ?? [])->filter(fn ($item) => filled(data_get($item, 'question')));
    $socialLinks = collect($settings->social_links ?? [])->filter(fn ($url) => filled($url));
@endphp

<section class="contact-hero-wrap">
    <div class="c">
        <header class="contact-hero">
            <div class="contact-hero-copy">
                <div class="contact-breadcrumb">
                    <a href="{{ route('home') }}">خانه</a>
                    <span>‹</span>
                    <span>{{ $pageTitle }}</span>
                </div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $pageDescription }}</p>
            </div>

            <div class="contact-hero-highlights">
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#msg"/></svg>
                    <span>ثبت مستقیم پیام در کارتابل پشتیبانی</span>
                </div>
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#cap"/></svg>
                    <span>مشاوره انتخاب پایه و دوره مناسب</span>
                </div>
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#check"/></svg>
                    <span>پاسخگویی به دانش‌آموزان و اولیا</span>
                </div>
            </div>
        </header>
    </div>
</section>

<section class="contact-main">
    <div class="c">
        <div class="contact-grid">
            <form class="contact-card" method="POST" action="{{ route('public.contact.store') }}">
                @csrf
                <div class="contact-card-head">
                    <span class="rec-accent-bar" aria-hidden="true"></span>
                    <div>
                        <h2>ارسال پیام به {{ $settings->site_name ?? 'آکادمی شیخان' }}</h2>
                    </div>
                </div>

                @if($errors->any())
                    <div class="ac-alert" role="alert" style="margin:0 0 18px">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="contact-form-row">
                    <div class="contact-field-group">
                        <label for="name">نام و نام خانوادگی</label>
                        <input
                            class="contact-input"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="مثال: علی محمدی"
                            required
                            maxlength="120"
                        >
                        @error('name')<div class="ac-error">{{ $message }}</div>@enderror
                    </div>

                    <div class="contact-field-group">
                        <label for="contact">ایمیل یا شماره تماس</label>
                        <input
                            class="contact-input"
                            id="contact"
                            name="contact"
                            value="{{ old('contact') }}"
                            placeholder="0912... یا email@example.com"
                            required
                            maxlength="190"
                        >
                        @error('contact')<div class="ac-error">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="contact-field-group">
                    <label for="topic">موضوع پیام</label>
                    <select class="contact-input" id="topic" name="topic" required>
                        @foreach($topics as $topic)
                            <option value="{{ $topic }}" @selected(old('topic') === $topic)>{{ $topic }}</option>
                        @endforeach
                    </select>
                    @error('topic')<div class="ac-error">{{ $message }}</div>@enderror
                </div>

                <div class="contact-field-group">
                    <label for="body">متن پیام یا درخواست مشاوره</label>
                    <textarea
                        class="contact-input contact-textarea"
                        id="body"
                        name="body"
                        placeholder="سؤال خود درباره دوره‌ها، پایه تحصیلی یا پشتیبانی را بنویسید..."
                        required
                        maxlength="5000"
                    >{{ old('body') }}</textarea>
                    @error('body')<div class="ac-error">{{ $message }}</div>@enderror
                </div>

                <button class="contact-submit" type="submit">
                    <span>ثبت و ارسال پیام</span>
                    <svg class="i"><use href="#arrl"/></svg>
                </button>
            </form>

            <aside class="contact-aside">
                <div class="contact-card">
                    <div class="contact-card-head">
                        <span class="rec-accent-bar" style="background:linear-gradient(180deg,#6d28d9,#4c1d95)" aria-hidden="true"></span>
                        <h2>{{ $supportTitle }}</h2>
                    </div>

                    <p style="margin:0 0 12px;color:var(--mute,#64748b);font-size:14px;line-height:1.9">
                        {{ $supportBody }}
                    </p>

                    @if($socialLinks->isNotEmpty())
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
                            @foreach($socialLinks as $socialName => $socialUrl)
                                <a class="chip" href="{{ $socialUrl }}" target="_blank" rel="noopener noreferrer">
                                    {{ is_string($socialName) ? $socialName : 'شبکه اجتماعی' }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="contact-quick-links">
                        <a class="contact-quick-link" href="{{ route('public.courses.index') }}">
                            <svg class="i"><use href="#cap"/></svg>
                            <span>مشاهده دوره‌ها</span>
                        </a>
                        <a class="contact-quick-link" href="{{ route('public.teachers.index') }}">
                            <svg class="i"><use href="#user"/></svg>
                            <span>معرفی مدرسان</span>
                        </a>
                    </div>
                </div>

                @if($faqItems->isNotEmpty())
                    <div class="contact-card contact-faq">
                        <div class="contact-card-head">
                            <span class="rec-accent-bar" aria-hidden="true"></span>
                            <h2>{{ $faqTitle }}</h2>
                        </div>

                        @foreach($faqItems as $index => $faq)
                            <details @if($index === 0) open @endif>
                                <summary>{{ data_get($faq, 'question', '') }}</summary>
                                <p>{{ data_get($faq, 'answer', '') }}</p>
                            </details>
                        @endforeach
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
