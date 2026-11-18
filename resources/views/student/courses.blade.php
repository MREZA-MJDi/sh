@extends('student.layouts.app')
@section('title', 'دوره‌های من')
@section('content')
<section class="page" id="courses"><div class="card"><div class="chead"><h2>دوره‌های من و دوره‌های رایگان</h2><span class="pill">{{ $courses->total() }} دوره</span></div>
 <div class="g3">
  @forelse ($courses as $course)
   @php $progress = $progressByCourse->get($course->id); @endphp
   <div class="card cc"><div class="th"><svg class="i"><use href="#i-book"/></svg></div><h3>{{ $course->title }}</h3><p>{{ $course->teacher?->name ?? 'مدرس' }} · {{ $course->subject }} · {{ $course->grade }}</p>
    @if ($course->type === 'free' && ! $course->has_active_enrollment)
     <span class="tag">رایگان · ثبت‌نام آزاد</span>
     <a class="btn s" href="{{ route('public.courses.show', $course->slug) }}">مشاهده دوره رایگان</a>
     <form method="POST" action="{{ route('student.courses.enroll-free', $course) }}">@csrf<button class="btn s" type="submit">ثبت‌نام رایگان و شروع</button></form>
    @else
     <div class="prog"><i style="width:{{ (int) data_get($progress, 'progress_percent', 0) }}%"></i></div><p style="margin:6px 0 0">{{ (int) data_get($progress, 'progress_percent', 0) }}٪ تکمیل</p>
     <a class="btn s" href="{{ route('student.courses.show', $course) }}">ادامه یادگیری</a>
    @endif
   </div>
  @empty
   <p>دوره‌ی فعالی برای حساب شما ثبت نشده است.</p>
  @endforelse
 </div>
 {{ $courses->links() }}
</div></section>
@endsection