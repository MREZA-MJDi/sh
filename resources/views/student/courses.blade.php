@extends('student.layouts.app')
@section('title', 'دوره‌های من')
@section('content')
<section class="page" id="courses"><div class="card"><div class="chead"><h2>دوره‌های من و دوره‌های رایگان</h2><span class="pill">{{ $courses->total() }} دوره</span></div>
 <div class="g3">
  @forelse ($courses as $course)
   @php $progress = $progressByCourse->get($course->id); @endphp
   <div class="card cc"><div class="th"><svg class="i"><use href="#i-book"/></svg></div><h3>{{ $course->title }}</h3><p>{{ $course->teacher?->name ?? 'مدرس' }} · {{ $course->subject }} · {{ $course->grade }} @if($course->season) · {{ \App\Models\Course::SEASONS[$course->season] ?? '' }} @endif @if($course->academic_year) · {{ $course->academic_year }} @endif</p>
    @if ($course->type === 'free')
     <span class="tag">رایگان · دسترسی فعال برای دانش‌آموزان</span>
     <div class="prog"><i style="width:{{ (int) data_get($progress, 'progress_percent', 0) }}%"></i></div>
     <p style="margin:6px 0 0">{{ (int) data_get($progress, 'progress_percent', 0) }}٪ تکمیل</p>
     <a class="btn s" href="{{ route('student.courses.show', $course) }}">شروع یادگیری</a>
    @else
     <div class="prog"><i style="width:{{ (int) data_get($progress, 'progress_percent', 0) }}%"></i></div><p style="margin:6px 0 0">{{ (int) data_get($progress, 'progress_percent', 0) }}٪ تکمیل</p>
     <a class="btn s" href="{{ route('student.courses.show', $course) }}">ادامه یادگیری</a>
    @endif
   </div>
  @empty
   <x-portal.empty-state icon="folder" title="دوره‌ای برای شما در دسترس نیست." description="پس از ثبت‌نام، دوره‌های آموزشی شما در این بخش نمایش داده می‌شوند."><a class="btn s" href="{{ route('public.courses.index') }}">مشاهده دوره‌های آموزشی</a></x-portal.empty-state>
  @endforelse
 </div>
 {{ $courses->links() }}
</div></section>
@endsection