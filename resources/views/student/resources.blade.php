@extends('student.layouts.app')

@section('title', 'جزوه‌ها و منابع')

@section('content')
<section class="page" id="resources"><div class="card"><div class="chead"><h2>جزوه‌ها و منابع</h2><span class="pill">۶ فایل</span></div>
 <div class="g3">
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#6fb2b6,#3f7f9c)"><svg class="i"><use href="{{ url('student/i-file') }}"/></svg></div><h3>جزوه‌ی فصل ۳ ریاضی</h3><p>PDF · ۲٫۴ مگابایت</p><a class="btn s" href="#">دانلود</a></div>
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#9b9acb,#4a4b8a)"><svg class="i"><use href="{{ url('student/i-file') }}"/></svg></div><h3>نمونه‌سؤال فیزیک</h3><p>PDF · ۱٫۱ مگابایت</p><a class="btn s" href="#">دانلود</a></div>
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#c3d2dd,#2f7483)"><svg class="i"><use href="{{ url('student/i-video') }}"/></svg></div><h3>ویدئوی جلسه‌ی ۱۶</h3><p>ضبط کلاس · ۵۲ دقیقه</p><a class="btn s" href="#">تماشا</a></div>
 </div></div></section>
@endsection
