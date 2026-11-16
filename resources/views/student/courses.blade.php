@extends('student.layouts.app')

@section('title', 'دوره‌های من')

@section('content')
<section class="page" id="courses"><div class="card"><div class="chead"><h2>دوره‌های من</h2><span class="pill">۳ دوره فعال</span></div>
 <div class="g3">
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#6fb2b6,#4b6fb3)"><svg class="i"><use href="{{ url('student/i-book') }}"/></svg></div><h3>ریاضی ۲</h3><p>استاد رضایی · ۲۴ جلسه</p><div class="prog"><i style="width:68%"></i></div><p style="margin:6px 0 0">۶۸٪ تکمیل</p><a class="btn s" href="{{ url('student/live-classes') }}">ادامه یادگیری</a></div>
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#9b9acb,#4a4b8a)"><svg class="i"><use href="{{ url('student/i-book') }}"/></svg></div><h3>فیزیک پیشرفته</h3><p>استاد احمدی · ۲۰ جلسه</p><div class="prog"><i style="width:45%"></i></div><p style="margin:6px 0 0">۴۵٪ تکمیل</p><a class="btn s" href="{{ url('student/live-classes') }}">ادامه یادگیری</a></div>
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#c3d2dd,#2f7483)"><svg class="i"><use href="{{ url('student/i-book') }}"/></svg></div><h3>زبان انگلیسی</h3><p>استاد کاظمی · ۳۰ جلسه</p><div class="prog"><i style="width:82%"></i></div><p style="margin:6px 0 0">۸۲٪ تکمیل</p><a class="btn s" href="{{ url('student/live-classes') }}">ادامه یادگیری</a></div>
 </div></div></section>
@endsection
