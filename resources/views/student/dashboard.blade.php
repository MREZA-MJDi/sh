@extends('student.layouts.app')

@section('title', 'مرکز یادگیری')

@section('content')
<section class="page" id="overview">\n @include("components.announcement-feed", ["announcements" => $announcements ?? collect()])
 <div class="hero">
  @if($student->avatarUrl())
   <img class="av" src="{{ $student->avatarUrl() }}" alt="{{ $student->name }}" style="border-radius:24px;object-fit:cover">
  @else
   <svg class="av" viewBox="0 0 120 120"><use href="#avatar"/></svg>
  @endif
  <div class="txt">
   <h2>سلام {{ $student->name }}!</h2>
   <p>{{ number_format($classroom_count) }} کلاس فعال داری و {{ number_format($pending_homework_count) }} تکلیف در انتظار ارسال است.</p>
   <a class="btn" href="{{ url('student/courses') }}">ادامه یادگیری</a>
  </div>
 </div>

 <div class="stats">
  <a class="st s1" href="{{ url('student/live-classes') }}" id="classes">
   <div class="ic"><svg class="i"><use href="#i-video"/></svg></div>
   <small>جلسات پیش‌رو</small><b>{{ number_format($upcoming_session_count) }}</b><span>برای مشاهده برنامه جلسات</span>
  </a>
  <a class="st s2" href="{{ url('student/homework') }}" id="homework">
   <div class="ic"><svg class="i"><use href="#i-task"/></svg></div>
   <small>تکالیف من</small><b>{{ number_format($homework_count) }}</b><span>{{ number_format($pending_homework_count) }} تکلیف در انتظار ارسال</span>
  </a>
  <a class="st s3" href="{{ url('student/courses') }}" id="classrooms">
   <div class="ic"><svg class="i"><use href="#i-book"/></svg></div>
   <small>کلاس‌های فعال</small><b>{{ number_format($classroom_count) }}</b><span>مشاهده کلاس‌های ثبت‌شده</span>
  </a>
  <a class="st s4" href="{{ url('student/exams') }}" id="results">
   <div class="ic"><svg class="i"><use href="#i-exam"/></svg></div>
   <small>نتایج آزمون‌ها</small><b>{{ number_format($result_count) }}</b><span>مشاهده کارنامه و بازخورد</span>
  </a>
 </div>

 <div class="row">
  <div class="card" id="sessions-notice">
   <div class="chead"><h2>جلسات و کلاس‌های آنلاین</h2><a class="pill" href="{{ url('student/live-classes') }}">مشاهده جلسات</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#9bd3d0,#4b6fb3)"></div>
    <div><b>{{ $upcoming_session_count > 0 ? 'برنامه جلساتت آماده است' : 'جلسه برنامه‌ریزی‌شده‌ای نداری' }}</b><span>{{ number_format($upcoming_session_count) }} جلسه پیش‌رو · جزئیات، زمان و وضعیت ورود در صفحه جلسات</span></div>
    <a class="pill" href="{{ url('student/live-classes') }}">باز کردن</a>
   </div>
  </div>

  <div class="card" id="homework-notice">
   <div class="chead"><h2>تکالیف</h2><a class="pill" href="{{ url('student/homework') }}">همه تکالیف</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#f4d59b,#d58a45)"></div>
    <div><b>{{ $pending_homework_count > 0 ? 'تکلیف در انتظار انجام داری' : 'تکلیف معوقی نداری' }}</b><span>{{ number_format($pending_homework_count) }} تکلیف در انتظار ارسال از {{ number_format($homework_count) }} تکلیف منتشرشده · مهلت و وضعیت در صفحه تکالیف</span></div>
    <a class="pill" href="{{ url('student/homework') }}">باز کردن</a>
   </div>
  </div>
 </div>

 <div class="row">
  <div class="card" id="classrooms-notice">
   <div class="chead"><h2>کلاس‌های ثبت‌شده</h2><a class="pill" href="{{ url('student/courses') }}">همه کلاس‌ها</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#b9a6e8,#7560b7)"></div>
    <div><b>{{ $classroom_count > 0 ? 'کلاس‌هایت در دسترس‌اند' : 'هنوز کلاسی ثبت نشده است' }}</b><span>{{ number_format($classroom_count) }} کلاس فعال · فهرست دوره‌ها و جزئیات در صفحه کلاس‌های من</span></div>
    <a class="pill" href="{{ url('student/courses') }}">باز کردن</a>
   </div>
  </div>

  <div class="card" id="videos-notice">
   <div class="chead"><h2>ویدیوهای ضبط‌شده</h2><a class="pill" href="{{ route('student.videos.index') }}">همه ویدیوها</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#a8c7e8,#4778ad)"></div>
    <div><b>ادامه یادگیری با ویدیوها</b><span>ویدیوهای جلسات و محتوای ضبط‌شده را در کتابخانه ویدیو ببین.</span></div>
    <a class="pill" href="{{ route('student.videos.index') }}">باز کردن</a>
   </div>
  </div>
 </div>

 <div class="row">
  <div class="card" id="exams-notice">
   <div class="chead"><h2>آزمون‌ها و کارنامه</h2><a class="pill" href="{{ url('student/exams') }}">همه آزمون‌ها</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#f0b7c2,#b85f7c)"></div>
    <div><b>{{ $result_count > 0 ? 'نتایج و بازخوردها آماده مشاهده‌اند' : 'آزمون‌هایت را پیگیری کن' }}</b><span>{{ number_format($result_count) }} نتیجه ثبت‌شده · آزمون‌ها، نمره‌ها و بازخورد مدرس در صفحه آزمون‌ها</span></div>
    <a class="pill" href="{{ url('student/exams') }}">باز کردن</a>
   </div>
  </div>

  <div class="card" id="resources-notice">
   <div class="chead"><h2>جزوه‌ها و منابع آموزشی</h2><a class="pill" href="{{ route('student.resources.index') }}">همه منابع</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#a9d7b7,#4d9870)"></div>
    <div><b>منابع آموزشی کلاس‌هایت</b><span>جزوه‌ها، فایل‌ها و منابع منتشرشده را در صفحه منابع ببین.</span></div>
    <a class="pill" href="{{ route('student.resources.index') }}">باز کردن</a>
   </div>
  </div>
 </div>

 <div class="row">
  <div class="card" id="attendance-notice">
   <div class="chead"><h2>حضور و غیاب</h2><a class="pill" href="{{ url('student/attendance') }}">مشاهده گزارش</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#9edbd2,#438f88)"></div>
    <div><b>گزارش حضور و غیابت را بررسی کن</b><span>وضعیت حضور جلسات و سوابق هر ماه در صفحه حضور و غیاب نمایش داده می‌شود.</span></div>
    <a class="pill" href="{{ url('student/attendance') }}">باز کردن</a>
   </div>
  </div>

  <div class="card" id="messages-notice">
   <div class="chead"><h2>پیام‌ها و ارتباط با مدرس</h2><a class="pill" href="{{ route('student.messages.index') }}">مشاهده پیام‌ها</a></div>
   <div class="trk">
    <div class="th" style="background:linear-gradient(145deg,#c5c8d8,#777f9f)"></div>
    <div><b>پیام‌هایت را از دست نده</b><span>گفت‌وگوها و پیام‌های مربوط به کلاس‌ها در بخش پیام‌ها قرار دارند.</span></div>
    <a class="pill" href="{{ route('student.messages.index') }}">باز کردن</a>
   </div>
  </div>
 </div>
</section>
@endsection
