@extends('public.layouts.academy')

@section('title', ($settings->home_sections['courses_page_title'] ?? 'دوره‌های آموزشی') . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'شیخان'))
@section('description', $settings->home_sections['courses_page_description'] ?? $settings->default_description)

@section('content')
<section class="contact-hero-wrap">
    <div class="c">
        <header class="contact-hero">
            <div class="contact-hero-copy">
                <div class="contact-breadcrumb">
                    <a href="{{ route('home') }}">خانه</a>
                    <span>‹</span>
                    <span>دوره‌ها</span>
                </div>
                <h1>{{ $settings->home_sections['courses_page_title'] ?? 'دوره‌های آموزشی' }}</h1>
                <p>{{ $settings->home_sections['courses_page_description'] ?? $settings->default_description }}</p>
            </div>

            <div class="contact-hero-highlights">
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#cap"/></svg>
                    <span>تفکیک بر اساس پایه تحصیلی و درس</span>
                </div>
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#cam"/></svg>
                    <span>دوره‌های ویدیویی، آنلاین و ترکیبی</span>
                </div>
                <div class="contact-highlight-pill">
                    <svg class="i"><use href="#check"/></svg>
                    <span>دسترسی فوری پس از ثبت‌نام</span>
                </div>
            </div>
        </header>
    </div>
</section>

<div class="c">
    {{-- چیپ‌های دسترسی سریع به پایه‌ها --}}
    <div class="chips" style="padding-top:28px">
        @php
            $currentGrade = request('grade');
            $gradeList = collect($grades)->mapWithKeys(function ($grade) {
                $value = (string) $grade;
                $label = $value === 'کنکور' || str_starts_with($value, 'پایه ') ? $value : 'پایه '.$value;
                return [$value => $label];
            })->prepend('همه پایه‌ها', '')->all();
        @endphp
        @foreach($gradeList as $val => $title)
            <a class="chip {{ (string)$currentGrade === (string)$val ? 'on' : '' }}" 
               href="{{ route('public.courses.index', array_merge(request()->except(['grade', 'page']), $val ? ['grade' => $val] : [])) }}">
                {{ $title }}
            </a>
        @endforeach
    </div>

    <div class="lay">
        {{-- سایدبار فیلتر استیکی --}}
        <aside class="card flt">
            <form method="GET" action="{{ route('public.courses.index') }}" id="filterForm">
                @if(request('grade'))
                    <input type="hidden" name="grade" value="{{ request('grade') }}">
                @endif

                <h4>سال تحصیلی</h4>
                <select class="inp" name="academic_year" onchange="this.form.submit()">
                    <option value="">همه سال‌ها</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" @selected((string) request('academic_year') === (string) $year)>{{ $year }}–{{ $year + 1 }}</option>
                    @endforeach
                </select>
                <h4>فصل آموزشی</h4>
                <select class="inp" name="season" onchange="this.form.submit()">
                    <option value="">همه فصل‌ها</option>
                    @foreach($seasons as $seasonValue => $seasonLabel)
                        <option value="{{ $seasonValue }}" @selected(request('season') === $seasonValue)>{{ $seasonLabel }}</option>
                    @endforeach
                </select>

                <input class="inp" id="qs" name="q" value="{{ request('q') }}" placeholder="جستجوی دوره…">

                <h4>درس و موضوع</h4>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <label>
                        <input type="radio" name="subject" value="" @checked(!request('subject')) onchange="this.form.submit()">
                        <span>همه موضوع‌ها</span>
                    </label>
                    @php
                        $allSubjects = collect($subjects)->filter();
                    @endphp
                    @foreach($allSubjects as $subj)
                        <label>
                            <input type="radio" name="subject" value="{{ $subj }}" @checked(request('subject') === $subj) onchange="this.form.submit()">
                            <span>{{ $subj }}</span>
                        </label>
                    @endforeach
                </div>

                <h4>قیمت</h4>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <label>
                        <input type="radio" name="price" value="" @checked(!request('price')) onchange="this.form.submit()">
                        <span>همه دوره‌ها</span>
                    </label>
                    <label>
                        <input type="radio" name="price" value="free" @checked(request('price') === 'free') onchange="this.form.submit()">
                        <span>رایگان</span>
                    </label>
                    <label>
                        <input type="radio" name="price" value="paid" @checked(request('price') === 'paid') onchange="this.form.submit()">
                        <span>پولی</span>
                    </label>
                </div>

                <div style="margin-top:20px;display:flex;flex-direction:column;gap:8px">
                    <button class="btn s" type="submit" style="width:100%">اعمال فیلتر</button>
                    @if(request()->hasAny(['q', 'subject', 'grade', 'season', 'academic_year', 'price', 'sort']))
                        <a class="btn s o" href="{{ route('public.courses.index') }}" style="width:100%;text-align:center;color:var(--mute)">
                            پاک کردن فیلترها
                        </a>
                    @endif
                </div>
            </form>
        </aside>

        {{-- لیست دوره‌ها --}}
        <div>
            <div class="tb">
                <b style="font-size:16px;color:var(--ink)">
                    {{ number_format($courses->total()) }} دوره
                </b>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:13px;color:var(--mute)">مرتب‌سازی:</span>
                    <select class="inp" style="width:auto;margin:0;padding:8px 12px;font-size:13px" 
                            name="sort" onchange="const url = new URL(window.location.href); url.searchParams.set('sort', this.value); url.searchParams.delete('page'); window.location.assign(url.toString());">
                        <option value="latest" @selected(request('sort', 'latest') === 'latest')>جدیدترین</option>
                        <option value="popular" @selected(request('sort') === 'popular')>پرطرفدارترین</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>ارزان‌ترین</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>گران‌ترین</option>
                    </select>
                </div>
            </div>

            @if($courses->count())
                <div class="g3">
                    @foreach($courses as $course)
                        <x-public.academy.course-card :course="$course" />
                    @endforeach
                </div>
                <div style="margin-top:36px">
                    {{ $courses->links() }}
                </div>
            @else
                <div class="card" style="padding:48px 24px;text-align:center;color:var(--mute)">
                    <div style="font-size:42px;margin-bottom:12px">✦</div>
                    <h3 style="margin:0 0 8px;color:var(--ink)">دوره‌ای با این مشخصات یافت نشد</h3>
                    <p style="margin:0 0 16px;font-size:14px">می‌توانی فیلترها را تغییر دهی یا همه دوره‌ها را مشاهده کنی.</p>
                    <a class="btn s" href="{{ route('public.courses.index') }}">مشاهده همه دوره‌ها</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
