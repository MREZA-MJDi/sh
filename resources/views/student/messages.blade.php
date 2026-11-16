@extends('student.layouts.app')

@section('title', 'پیام به مدرس‌ها')

@section('content')
<section class="page" id="messages"><div class="chat">
 <div class="card"><div class="chead"><h2>مدرس‌ها</h2></div>
  <div class="trk"><div class="th" style="background:linear-gradient(160deg,#9bd3d0,#4b6fb3);border-radius:50%"></div><div><b>استاد رضایی</b><span>ریاضی</span></div><span class="tag">۲</span></div>
  <div class="trk"><div class="th" style="background:linear-gradient(160deg,#c9c6e8,#7c7db3);border-radius:50%"></div><div><b>استاد احمدی</b><span>فیزیک</span></div><span class="tag">۱</span></div>
  <div class="trk"><div class="th" style="background:linear-gradient(160deg,#c3d2dd,#6f8fb3);border-radius:50%"></div><div><b>استاد کاظمی</b><span>زبان</span></div><span class="tag">۱</span></div></div>
 <div class="card"><div class="chead"><h2>گفتگو با استاد رضایی</h2></div>
  <div class="msgs"><div class="m">سلام آرین، تمرین فصل ۳ تا فردا ارسال بشه.</div><div class="m me">سلام استاد، سؤال ۷ رو متوجه نشدم.</div><div class="m">جلسه‌ی بعد توضیح می‌دم؛ فعلاً به مثال صفحه‌ی ۴۲ نگاه کن.</div></div>
  <form class="frm" method="POST" action="{{ url('student/messages') }}">@csrf<input class="fld" name="message" placeholder="پیامت رو بنویس..." autocomplete="off" required><button class="btn s" type="submit">ارسال</button></form></div></div></section>
@endsection
