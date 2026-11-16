@extends('student.layouts.app')

@section('title', 'یادداشت‌ها')

@section('content')
<section class="page" id="notes"><div class="card"><div class="chead"><h2>یادداشت‌ها</h2></div>
 <div class="g3"><div class="note">فرمول شتاب: F = m·a. قبل از آزمون مرور شود.</div><div class="note">سؤال ۷ تمرین ریاضی را از استاد بپرسم.</div><div class="note">لغات درس ۵ زبان را تا پنجشنبه حفظ کنم.</div></div>
 <form class="frm" method="POST" action="{{ url('student/notes') }}">@csrf<input class="fld" name="note" placeholder="یادداشت جدید..." autocomplete="off" required><button class="btn s" type="submit">افزودن</button></form></div></section>
@endsection
