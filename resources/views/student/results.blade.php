@extends('student.layouts.app')

@section('title', 'نمرات و عملکرد')

@section('content')
<section class="page" id="results"><div class="stats">
 <div class="st s1"><div class="ic"><svg class="i"><use href="{{ url('student/i-chart') }}"/></svg></div><small>میانگین نمرات</small><b>۱۷٫۸</b><span>از ۲۰</span></div>
 <div class="st s2"><div class="ic"><svg class="i"><use href="{{ url('student/i-trophy') }}"/></svg></div><small>رتبه در کلاس</small><b>۳</b><span>از ۲۸ نفر</span></div>
 <div class="st s3"><div class="ic"><svg class="i"><use href="{{ url('student/i-exam') }}"/></svg></div><small>آزمون‌های داده‌شده</small><b>۹</b><span>این ترم</span></div>
 <div class="st s4"><div class="ic"><svg class="i"><use href="{{ url('student/i-task') }}"/></svg></div><small>تکالیف تحویل‌شده</small><b>۱۴</b><span>بدون تأخیر</span></div></div>
 <div class="row"><div class="card"><div class="chead"><h2>نمره‌ی هر درس</h2></div><div class="bars"><div class="cols">
  <div class="col"><div style="height:calc((100% - 26px)*.9);background:linear-gradient(180deg,#8cc6d6,#5db0b4)"></div>ریاضی</div>
  <div class="col"><div style="height:calc((100% - 26px)*.8);background:linear-gradient(180deg,#5b8fc0,#3f86c4)"></div>فیزیک</div>
  <div class="col"><div style="height:calc((100% - 26px)*.95);background:linear-gradient(180deg,#5db0b4,#2f7483)"></div>زبان</div>
  <div class="col"><div style="height:calc((100% - 26px)*.7);background:linear-gradient(180deg,#9b9acb,#4a4b8a)"></div>شیمی</div></div></div></div>
 <div class="card"><div class="chead"><h2>آخرین نمرات</h2></div><div class="tw"><table class="tbl"><tr><td>آزمون زبان ۱</td><td>۱۷</td></tr><tr><td>تمرین فصل ۲</td><td>۱۹</td></tr><tr><td>کوییز فیزیک</td><td>۱۶</td></tr></table></div></div></div></section>
@endsection
