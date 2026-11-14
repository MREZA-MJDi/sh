@extends('public.layouts.academy')

@section('title', 'دوره‌های آموزشی' . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'شیخان'))
@section('description', 'فهرست دوره‌های آموزشی منتشرشده آکادمی شیخان بر اساس پایه تحصیلی و موضوع')

@section('content')
<div class="pgh">
    <div class="c">
        <div class="bc">
            <a href="{{ route('home') }}">خانه</a> ‹ <span>دوره‌ها</span>
        </div>
        <h1>دوره‌های آموزشی</h1>
        <p>بر اساس پایه تحصیلی و درس، دوره مناسب خودت را انتخاب کن</p>
    </div>
</div>

<div class="c">
    {{-- چیپ‌های دسترسی سریع به پایه‌ها --}}
    <div class="chips" style="padding-top:28px">
        @php
            $currentGrade = request('grade');
            $gradeList = [
                '' => 'همه پایه‌ها',
                'دهم' => 'پایه دهم',
                'یازدهم' => 'پایه یازدهم',
                'دوازدهم' => 'پایه دوازدهم',
                'کنکور' => 'پایه سیزدهم (کنکور)',
            ];
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

                <input class="inp" id="qs" name="q" value="{{ request('q') }}" placeholder="جستجوی دوره…">

                <h4>درس و موضوع</h4>
                <div style="display:flex;flex-direction:column;gap:4px">
                    <label>
                        <input type="radio" name="subject" value="" @checked(!request('subject')) onchange="this.form.submit()">
                        <span>همه موضوع‌ها</span>
                    </label>
                    @php
                        $allSubjects = collect($subjects)->merge(['ریاضی', 'فیزیک', 'شیمی', 'زیست', 'زبان'])->unique()->filter();
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
                    @if(request()->hasAny(['q', 'subject', 'grade', 'price', 'sort']))
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
                            name="sort" onchange="location.href = '{{ route('public.courses.index', array_merge(request()->except(['sort', 'page']))) }}' + (this.value ? (location.search ? '&' : '?') + 'sort=' + this.value : '')">
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
