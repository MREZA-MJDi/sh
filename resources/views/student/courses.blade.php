@extends('student.layouts.app')
@section('title', 'دوره‌های من')
@section('hide-topbar-title', '1')
@section('content')
<section class="page" id="courses">
<x-portal.page-head icon="book" tone="education" title="دوره‌های من">
 <x-slot:subtitle>دوره‌های ثبت‌نام‌شده اول نمایش داده می‌شوند. دوره‌های رایگان را هم می‌توانی از همین صفحه شروع کنی.</x-slot:subtitle>
</x-portal.page-head>
<div class="card"><div class="chead"><h2>فهرست دوره‌ها</h2><span class="pill">{{ $courses->total() }} دوره</span></div>
 <div class="g3">
  @forelse ($courses as $course)
   @php
    $progress = $progressByCourse->get($course->id);
    $percent = (int) data_get($progress, 'progress_percent', 0);
   @endphp
   <div class="card cc"><div class="th"><svg class="i"><use href="#i-book"/></svg></div><h3>{{ $course->title }}</h3><p>{{ $course->teacher?->name ?? 'مدرس' }} · {{ $course->subject }} · {{ $course->grade }} @if($course->season) · {{ \App\Models\Course::SEASONS[$course->season] ?? '' }} @endif @if($course->academic_year) · {{ $course->academic_year }} @endif</p>
    <span class="tag">{{ $course->has_active_enrollment ? 'دسترسی فعال' : 'رایگان · دسترسی فعال برای دانش‌آموزان' }}</span>
    <div class="prog" aria-hidden="true"><i style="width:{{ $percent }}%"></i></div>
    <p>{{ $percent > 0 ? $percent.'٪ تکمیل‌شده' : 'هنوز شروع نکرده‌ای' }}</p>
    <a class="btn s" href="{{ route('student.courses.show', $course) }}">{{ $percent > 0 ? 'ادامه یادگیری' : 'شروع یادگیری' }}</a>
   </div>
  @empty
   <x-portal.empty-state icon="folder" title="دوره‌ای برای شما در دسترس نیست." description="پس از ثبت‌نام، دوره‌های آموزشی شما در این بخش نمایش داده می‌شوند."><a class="btn s" href="{{ route('public.courses.index') }}">مشاهده دوره‌های آموزشی</a></x-portal.empty-state>
  @endforelse
 </div>
 {{ $courses->links() }}
</div></section>
@endsection
