@extends('student.layouts.app')

@section('title', 'مرکز یادگیری')

@section('content')
@php
    $sessions = $upcoming_sessions ?? collect();
    $tasks = $homeworks ?? collect();
    $courseItems = $course_progress ?? collect();
@endphp
<section class="page" id="dashboard">
 <div class="hero">
  <svg class="av" viewBox="0 0 120 120"><use href="#avatar"/></svg>
  <div class="txt">
   <h2>سلام {{ $student->name }}!</h2>
   <p>{{ $classroom_count }} کلاس فعال و {{ $pending_homework_count }} تکلیف ثبت‌نشده داری.</p>
   <a class="btn" href="{{ url('student/courses') }}">ادامه یادگیری</a>
  </div>
 </div>
 <div class="stats">
  <a class="st s1" href="{{ url('student/live-classes') }}"><div class="ic"><svg class="i"><use href="#i-video"/></svg></div><small>کلاس‌های پیش‌رو</small><b>{{ $sessions->count() }}</b><span>جلسات برنامه‌ریزی‌شده</span></a>
  <a class="st s2" href="{{ url('student/homework') }}"><div class="ic"><svg class="i"><use href="#i-task"/></svg></div><small>تکالیف نمایش‌داده‌شده</small><b>{{ $tasks->count() }}</b><span>{{ $pending_homework_count }} تکلیف بدون ارسال</span></a>
  <a class="st s3" href="{{ url('student/exams') }}"><div class="ic"><svg class="i"><use href="#i-exam"/></svg></div><small>نتایج اخیر</small><b>{{ $results->count() }}</b><span>نتایج قابل مشاهده</span></a>
  <a class="st s4" href="{{ url('student/messages') }}"><div class="ic"><svg class="i"><use href="#i-msg"/></svg></div><small>یادداشت‌ها</small><b>{{ $notes->count() }}</b><span>یادداشت‌های شخصی</span></a>
 </div>
 <div class="row">
  <div class="card">
   <div class="chead"><h2>کلاس‌های پیش‌رو</h2><a class="pill" href="{{ url('student/live-classes') }}">همه</a></div>
   @forelse ($sessions as $session)
    <div class="trk">
     <div class="th" style="background:linear-gradient(160deg,#9bd3d0,#4b6fb3)"></div>
     <div><b>{{ $session->classroom?->name ?? 'کلاس' }}</b><span>{{ $session->starts_at?->format('Y/m/d H:i') }}</span></div>
     <form method="POST" action="{{ route('student.class-sessions.join', $session) }}">@csrf<button class="pl" type="submit" aria-label="ورود به کلاس"><svg class="i"><use href="#i-play"/></svg></button></form>
    </div>
   @empty
    <p>جلسه‌ی برنامه‌ریزی‌شده‌ای برای شما ثبت نشده است.</p>
   @endforelse
  </div>
  <div class="card">
   <div class="chead"><h2>تکالیف من</h2><a class="pill" href="{{ url('student/homework') }}">همه</a></div>
   @forelse ($tasks->take(5) as $homework)
    <div class="trk">
     <div class="th" style="background:linear-gradient(160deg,#c3d2dd,#6f8fb3)"></div>
     <div><b>{{ $homework->title }}</b><span>{{ $homework->classroom?->name }} · {{ $homework->due_at?->format('Y/m/d H:i') ?? 'بدون مهلت' }}</span></div>
     <span class="tag">{{ $homework->submitted_by_student_count ? 'ارسال‌شده' : 'در انتظار' }}</span>
    </div>
   @empty
    <p>تکلیفی برای کلاس‌های فعال شما ثبت نشده است.</p>
   @endforelse
  </div>
 </div>
 <div class="row">
  <div class="card">
   <div class="chead"><h2>پیشرفت دوره‌ها</h2><a class="pill" href="{{ url('student/courses') }}">همه</a></div>
   @forelse ($courseItems as $course)
    <div class="trk"><div><b>{{ data_get($course, 'title') }}</b><span>{{ data_get($course, 'completed_lessons', 0) }} از {{ data_get($course, 'total_lessons', 0) }} درس</span></div><div class="prog"><i style="width:{{ (int) data_get($course, 'progress_percent', 0) }}%"></i></div></div>
   @empty
    <p>دوره‌ی فعالی برای نمایش وجود ندارد.</p>
   @endforelse
  </div>
  <div class="card">
   <div class="chead"><h2>اطلاعیه‌ها</h2></div>
   @forelse (($announcements ?? collect()) as $announcement)
    <div class="trk"><div><b>{{ $announcement->title }}</b><span>{{ $announcement->published_at?->format('Y/m/d') }}</span><p>{{ \Illuminate\Support\Str::limit(strip_tags($announcement->body), 150) }}</p></div></div>
   @empty
    <p>اطلاعیه‌ی تازه‌ای ثبت نشده است.</p>
   @endforelse
  </div>
 </div>
</section>
@endsection
