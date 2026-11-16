@extends('student.layouts.app')

@section('title', 'کلاس‌های من')

@section('content')
<section class="page" id="live-classes"><div class="card"><div class="chead"><h2>کلاس‌های من</h2><span class="tag">یک کلاس در حال برگزاری</span></div>
 <div class="trk"><div class="th" style="background:linear-gradient(160deg,#9bd3d0,#4b6fb3)"></div><div><b>ریاضی ۲ · جلسه ۱۷</b><span>امروز ۱۴:۰۰ · در حال برگزاری</span></div><a class="btn s" href="#">ورود به کلاس</a></div>
 <div class="trk"><div class="th" style="background:linear-gradient(160deg,#c9c6e8,#7c7db3)"></div><div><b>زبان انگلیسی · جلسه ۲۶</b><span>امروز ۱۶:۳۰</span></div><span class="tag w">به‌زودی</span></div>
 <div class="trk"><div class="th" style="background:linear-gradient(160deg,#c3d2dd,#6f8fb3)"></div><div><b>فیزیک پیشرفته · جلسه ۱۱</b><span>شنبه ۱۸:۰۰</span></div><a class="pill" href="#">یادآوری</a></div></div></section>
@endsection
