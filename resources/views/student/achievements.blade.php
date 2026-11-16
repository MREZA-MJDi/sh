@extends('student.layouts.app')
@section('title', 'دستاوردها')
@section('content')
<section class="page" id="achievements"><div class="card"><div class="chead"><h2>عملکرد و پیشرفت</h2></div>
 <div class="g3">
  <div class="card medal"><b>نرخ حضور</b><p>{{ $attendance_rate === null ? 'داده‌ای ثبت نشده' : $attendance_rate.'٪' }}</p></div>
  <div class="card medal"><b>نتایج آزمون</b><p>{{ ($results ?? collect())->count() }} نتیجه ثبت‌شده</p></div>
  <div class="card medal"><b>دوره‌های فعال</b><p>{{ ($course_progress ?? collect())->count() }} دوره</p></div>
 </div>
 @forelse (($course_progress ?? collect()) as $course)
  <div class="trk"><div><b>{{ data_get($course, 'title') }}</b><span>{{ data_get($course, 'completed_lessons', 0) }} از {{ data_get($course, 'total_lessons', 0) }} درس تکمیل‌شده</span></div><span class="tag">{{ data_get($course, 'progress_percent', 0) }}٪</span></div>
 @empty
  <p>هنوز پیشرفت دوره‌ای برای نمایش وجود ندارد.</p>
 @endforelse
</div></section>
@endsection