@extends('student.layouts.app')

@section('title', 'آزمون‌ها')

@section('content')
<section class="page" id="exams"><div class="card"><div class="chead"><h2>آزمون‌ها</h2><span class="pill">۲ آزمون پیش‌رو</span></div>
 <div class="tw"><table class="tbl"><tr><th>آزمون</th><th>زمان</th><th>مدت</th><th>وضعیت</th><th></th></tr>
 <tr><td>میان‌ترم فیزیک</td><td>شنبه ۱۰:۰۰</td><td>۶۰ دقیقه</td><td><span class="tag">آماده شروع</span></td><td><a class="btn s" href="{{ url('student/exams/take') }}">شروع آزمون</a></td></tr>
 <tr><td>کوییز ریاضی ۲</td><td>دوشنبه ۱۲:۰۰</td><td>۳۰ دقیقه</td><td><span class="tag w">به‌زودی</span></td><td></td></tr>
 <tr><td>آزمون زبان ۱</td><td>گذشته</td><td>۴۵ دقیقه</td><td><span class="tag d">نمره: ۱۷</span></td><td><a class="pill" href="{{ url('student/results') }}">نتیجه</a></td></tr></table></div></div></section>
@endsection
