@extends('student.layouts.app')

@section('title', 'حساب کاربری')

@section('content')
<section class="page" id="profile"><div class="card"><div class="chead"><h2>حساب کاربری</h2></div>
 <form method="POST" action="{{ url('student/profile') }}">@csrf<div class="pf"><div><label>نام و نام خانوادگی</label><input class="fld" name="name" value="{{ old('name', $student->name ?? '') }}"></div><div><label>شماره‌ی موبایل</label><input class="fld" name="mobile" value="{{ old('mobile', $student->mobile ?? '') }}"></div><div><label>ایمیل</label><input class="fld" name="email" value="{{ old('email', $student->email ?? '') }}"></div><div><label>پایه</label><input class="fld" name="grade" value="{{ old('grade', $student->grade ?? '') }}"></div></div>
 <div class="frm"><button class="btn s" type="submit">ذخیره‌ی تغییرات</button><a class="pill" href="{{ url('student/dashboard') }}">انصراف</a></div></form></div></section>
@endsection
