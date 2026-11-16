@extends('student.layouts.app')

@section('title', 'تکالیف')

@section('content')
<section class="page" id="assignments"><div class="card"><div class="chead"><h2>تکالیف</h2><span class="pill">۳ تکلیف باز</span></div>
 <div class="tw"><table class="tbl"><tr><th>تکلیف</th><th>درس</th><th>مهلت</th><th>وضعیت</th><th></th></tr>
 <tr><td>تمرین فصل ۳</td><td>ریاضی</td><td>فردا</td><td><span class="tag w">در انتظار</span></td><td><a class="btn s" href="#">ارسال پاسخ</a></td></tr>
 <tr><td>گزارش آزمایشگاه</td><td>فیزیک</td><td>۳ روز دیگر</td><td><span class="tag w">در انتظار</span></td><td><a class="btn s" href="#">ارسال پاسخ</a></td></tr>
 <tr><td>مقاله‌ی ۲۰۰ کلمه‌ای</td><td>زبان</td><td>پنجشنبه</td><td><span class="tag d">در حال تصحیح</span></td><td><a class="pill" href="#">مشاهده</a></td></tr>
 <tr><td>تمرین فصل ۲</td><td>ریاضی</td><td>گذشته</td><td><span class="tag">نمره: ۱۹</span></td><td><a class="pill" href="#">بازخورد</a></td></tr></table></div></div></section>
@endsection
