@extends('student.layouts.app')

@section('title', 'دستاوردها')

@section('content')
<section class="page" id="achievements"><div class="card"><div class="chead"><h2>دستاوردها</h2><span class="pill">۳ از ۶</span></div>
 <div class="g3">
  <div class="card medal"><div class="pl"><svg class="i"><use href="{{ url('student/i-trophy') }}"/></svg></div><b>تکالیف بدون تأخیر</b><p style="font-size:13px;color:var(--muted)">۱۴ تکلیف پشت‌سرهم</p></div>
  <div class="card medal"><div class="pl"><svg class="i"><use href="{{ url('student/i-cal') }}"/></svg></div><b>حضور کامل ماه</b><p style="font-size:13px;color:var(--muted)">مهر ۱۴۰۵</p></div>
  <div class="card medal"><div class="pl"><svg class="i"><use href="{{ url('student/i-exam') }}"/></svg></div><b>نمره‌ی ۲۰ در آزمون</b><p style="font-size:13px;color:var(--muted)">کوییز ریاضی</p></div>
  <div class="card medal off"><div class="pl"><svg class="i"><use href="{{ url('student/i-book') }}"/></svg></div><b>تکمیل یک دوره</b><p style="font-size:13px;color:var(--muted)">۸۲٪ مانده تا ۱۰۰٪</p></div></div></div></section>
@endsection
