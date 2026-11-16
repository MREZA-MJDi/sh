@extends('student.layouts.app')

@section('title', 'حساب کاربری')

@section('content')
<section class="page" id="profile"><div class="card"><div class="chead"><h2>اطلاعات پروفایل</h2></div>
 <form method="POST" action="{{ route('student.profile.update') }}">@csrf @method('PUT')<div class="pf"><div><label>نام و نام خانوادگی</label><input class="fld" name="name" value="{{ old('name', $student->name ?? '') }}"></div><div><label>شماره‌ی موبایل</label><input class="fld" name="phone" value="{{ old('phone', $student->phone ?? '') }}"></div><div><label>ایمیل</label><input class="fld" name="email" value="{{ $student->email ?? '' }}" readonly></div><div><label>پایه</label><input class="fld" name="grade" value="{{ $student->grade ?? '' }}" readonly></div></div>
 <div class="frm"><button class="btn s" type="submit">ذخیره‌ی تغییرات</button><a class="pill" href="{{ route('student.dashboard') }}">انصراف</a></div></form></div></section>
@endsection
