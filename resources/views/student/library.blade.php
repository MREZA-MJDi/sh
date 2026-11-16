@extends('student.layouts.app')

@section('title', 'کتابخانه و خریدها')

@section('content')
<section class="page" id="library"><div class="card"><div class="chead"><h2>کتابخانه و خریدهای من</h2><a class="pill" href="#">فروشگاه</a></div>
 <div class="g3">
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#6fb2b6,#4b6fb3)"><svg class="i"><use href="{{ url('student/i-book') }}"/></svg></div><h3>ریاضی دوازدهم</h3><p>کتاب الکترونیک · خریداری‌شده</p><a class="btn s" href="#">مطالعه</a></div>
  <div class="card cc"><div class="th" style="background:linear-gradient(160deg,#9b9acb,#4a4b8a)"><svg class="i"><use href="{{ url('student/i-cart') }}"/></svg></div><h3>بسته‌ی آزمون‌های زبان</h3><p>سفارش ۱۲۴۵ · پرداخت‌شده</p><a class="btn s" href="#">مشاهده</a></div>
 </div></div></section>
@endsection
