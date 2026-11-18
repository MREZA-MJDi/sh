@extends('public.layouts.academy')
@section('title',($settings->home_sections['teachers_page_title'] ?? 'مدرسان شیخان').$settings->title_separator.$settings->site_name)
@section('description',$settings->home_sections['teachers_page_description'] ?? $settings->default_description)
@section('content')
<header class="ac-page-head"><div class="ac-wrap"><div style="font-size:13px;color:#9fb0d8"><a href="{{ route('home') }}">خانه</a> ‹ مدرسان</div><h1>{{ $settings->home_sections['teachers_page_title'] ?? 'مدرسان شیخان' }}</h1><p>{{ $settings->home_sections['teachers_page_description'] ?? $settings->default_description }}</p></div></header>
<section class="ac-section"><div class="ac-wrap">
<form method="GET" action="{{ route('public.teachers.index') }}" class="ac-card" style="display:flex;gap:10px;flex-wrap:wrap;padding:16px;margin-bottom:22px">
<input class="ac-field" style="flex:1;min-width:190px;margin:0" type="search" name="q" value="{{ request('q') }}" placeholder="جستجوی نام مدرس یا درس">
<select class="ac-field" style="width:auto;min-width:170px;margin:0" name="subject"><option value="">همه درس‌ها</option>@foreach($subjects as $subject)<option value="{{ $subject }}" @selected(request('subject')===$subject)>{{ $subject }}</option>@endforeach</select>
<button class="ac-btn" type="submit">اعمال فیلتر</button><a class="ac-btn ac-btn-outline" href="{{ route('public.teachers.index') }}">پاک‌کردن</a>
</form>
@if($teachers->count())<div class="ac-grid ac-grid-4">@foreach($teachers as $teacher)<article class="ac-card ac-teacher"><a href="{{ route('public.teachers.show', $teacher) }}" style="text-decoration:none;color:inherit;display:block"><div class="ac-avatar">{{ mb_substr($teacher->name,0,1) }}</div><h3 style="margin-bottom:6px">{{ $teacher->name }}</h3></a><span class="ac-pill">{{ $teacher->subject ?: 'مدرس' }}</span><div class="ac-meta" style="justify-content:center;margin:10px 0"><span>{{ $teacher->published_courses_count }} دوره منتشرشده</span></div><a class="ac-btn ac-btn-outline ac-btn-small" style="width:100%;margin-bottom:12px" href="{{ route('public.teachers.show', $teacher) }}">مشاهده پروفایل مدرس ←</a></article>@endforeach</div><div style="margin-top:25px">{{ $teachers->links() }}</div>@else<div class="ac-empty">در حال حاضر مدرسی با این مشخصات پیدا نشد.</div>@endif
<div class="ac-cta" style="margin-top:42px"><h2>{{ $settings->home_sections['teachers_cta_title'] ?? '' }}</h2><p>{{ $settings->home_sections['teachers_cta_body'] ?? '' }}</p><a class="ac-btn" style="background:#fff;color:var(--ac-navy)!important" href="{{ route('login') }}">{{ $settings->home_sections['teachers_cta_label'] ?? 'ورود به حساب' }} ←</a></div>
</div></section>
@endsection
