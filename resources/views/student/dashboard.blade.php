@extends('student.layouts.app')

@section('title', 'مرکز یادگیری')

@section('content')
<section class="page" id="dashboard">
 <div class="hero"><svg class="av" viewBox="0 0 120 120"><use href="{{ url('student/avatar') }}"/></svg><div class="txt"><h2>عصر بخیر، آرین!</h2><p>امروز ۲ کلاس و ۱ تکلیف با مهلت فردا داری.</p><a class="btn" href="{{ url('student/live-classes') }}">ورود به کلاس آنلاین</a></div></div>
 <div class="stats">
  <a class="st s1" href="{{ url('student/live-classes') }}"><div class="ic"><svg class="i"><use href="{{ url('student/i-video') }}"/></svg></div><small>کلاس‌های امروز</small><b>۲</b><span>اولی ساعت ۱۴:۰۰</span></a>
  <a class="st s2" href="{{ url('student/assignments') }}"><div class="ic"><svg class="i"><use href="{{ url('student/i-task') }}"/></svg></div><small>تکالیف باز</small><b>۳</b><span>۱ مورد تا فردا</span></a>
  <a class="st s3" href="{{ url('student/exams') }}"><div class="ic"><svg class="i"><use href="{{ url('student/i-exam') }}"/></svg></div><small>آزمون پیش‌رو</small><b>۲</b><span>نزدیک‌ترین: شنبه</span></a>
  <a class="st s4" href="{{ url('student/messages') }}"><div class="ic"><svg class="i"><use href="{{ url('student/i-msg') }}"/></svg></div><small>پیام جدید</small><b>۴</b><span>از مدرس‌ها</span></a>
 </div>
 <div class="row">
  <div class="card"><div class="chead"><h2>کلاس‌های امروز</h2><a class="pill" href="{{ url('student/live-classes') }}">همه</a></div>
   <div class="trk"><div class="th" style="background:linear-gradient(160deg,#9bd3d0,#4b6fb3)"></div><div><b>ریاضی ۲</b><span>۱۴:۰۰ · استاد رضایی</span></div><a class="pl" href="{{ url('student/live-classes') }}"><svg class="i"><use href="{{ url('student/i-play') }}"/></svg></a></div>
   <div class="trk"><div class="th" style="background:linear-gradient(160deg,#c9c6e8,#7c7db3)"></div><div><b>زبان انگلیسی</b><span>۱۶:۳۰ · استاد کاظمی</span></div><a class="pl" href="{{ url('student/live-classes') }}"><svg class="i"><use href="{{ url('student/i-play') }}"/></svg></a></div></div>
  <div class="card"><div class="chead"><h2>مهلت‌های نزدیک</h2><a class="pill" href="{{ url('student/assignments') }}">همه</a></div>
   <div class="trk"><div class="th" style="background:linear-gradient(160deg,#c3d2dd,#6f8fb3)"></div><div><b>تمرین فصل ۳ ریاضی</b><span>مهلت: فردا</span></div><a class="pl" href="{{ url('student/assignments') }}"><svg class="i"><use href="{{ url('student/i-play') }}"/></svg></a></div>
   <div class="trk"><div class="th" style="background:linear-gradient(160deg,#9bd3d0,#2f7483)"></div><div><b>آزمون میان‌ترم فیزیک</b><span>شنبه ۱۰:۰۰</span></div><a class="pl" href="{{ url('student/exams/take') }}"><svg class="i"><use href="{{ url('student/i-play') }}"/></svg></a></div></div>
 </div>
</section>
@endsection
