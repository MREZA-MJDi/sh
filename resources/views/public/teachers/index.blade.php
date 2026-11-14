@extends('public.layouts.academy')
@section('title','مدرسان'.$settings->title_separator.$settings->site_name)
@section('content')
<header class="ac-page-head"><div class="ac-wrap"><div style="font-size:13px;color:#9fb0d8"><a href="{{ route('home') }}">خانه</a> ‹ مدرسان</div><h1>مدرسان شیخان</h1><p>مدرس‌های فعال و دوره‌های منتشرشده آن‌ها را ببین.</p></div></header>
<section class="ac-section"><div class="ac-wrap">
<form method="GET" action="{{ route('public.teachers.index') }}" class="ac-card" style="display:flex;gap:10px;flex-wrap:wrap;padding:16px;margin-bottom:22px">
<input class="ac-field" style="flex:1;min-width:190px;margin:0" type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی نام مدرس یا درس">
<select class="ac-field" style="width:auto;min-width:170px;margin:0" name="subject"><option value="">همه درس‌ها</option>@foreach($subjects as $subject)<option value="{{ $subject }}" @selected(request('subject')===$subject)>{{ $subject }}</option>@endforeach</select>
<button class="ac-btn" type="submit">اعمال فیلتر</button><a class="ac-btn ac-btn-outline" href="{{ route('public.teachers.index') }}">پاک‌کردن</a>
</form>
@if($teachers->count())<div class="ac-grid ac-grid-4">@foreach($teachers as $teacher)<article class="ac-card ac-teacher"><div class="ac-avatar">{{ mb_substr($teacher->name,0,1) }}</div><h3>{{ $teacher->name }}</h3><span class="ac-pill">{{ $teacher->subject ?: 'مدرس' }}</span><div class="ac-meta" style="justify-content:center;margin:10px 0"><span>{{ $teacher->published_courses_count }} دوره منتشرشده</span></div>@if($teacher->assignedCourses->isNotEmpty())<div style="border-top:1px solid var(--ac-line);padding-top:10px;text-align:right">@foreach($teacher->assignedCourses as $course)<a class="ac-link" style="display:block;font-size:12px;padding:3px 0" href="{{ route('public.courses.show',$course->slug) }}">{{ $course->title }}</a>@endforeach</div>@endif</article>@endforeach</div><div style="margin-top:25px">{{ $teachers->links() }}</div>@else<div class="ac-empty">در حال حاضر مدرسی با این مشخصات پیدا نشد.</div>@endif
<div class="ac-cta" style="margin-top:42px"><h2>مدرس هستی؟ با ما همکاری کن</h2><p>برای آشنایی با امکانات حساب مدرس، وارد حساب کاربری شو.</p><a class="ac-btn" style="background:#fff;color:var(--ac-navy)!important" href="{{ route('login') }}">ورود به حساب ←</a></div>
</div></section>
@endsection
