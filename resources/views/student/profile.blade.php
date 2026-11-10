@extends('layouts.app')
@section('title', 'پروفایل')
@section('content')
@php $done = collect($m['exams'])->where('status', 'done'); @endphp
<div class="grid2">
  <x-card title="اطلاعات حساب">
    <form method="POST" action="{{ route('student.profile.update') }}">@csrf @method('PUT')
      <div class="fm">
        <div><label for="name">نام و نام خانوادگی</label><input class="fld" id="name" name="name" value="{{ old('name', $user['name']) }}"><x-field-error name="name"/></div>
        <div><label for="phone">موبایل</label><input class="fld" id="phone" name="phone" value="{{ old('phone', $user['phone']) }}"><x-field-error name="phone"/></div>
        <div><label for="email">ایمیل</label><input class="fld" id="email" name="email" type="email" value="{{ old('email', $user['email']) }}"><x-field-error name="email"/></div>
        <div><label for="grade">پایه</label><input class="fld" id="grade" value="{{ $user['grade'] }}" disabled></div>
      </div>
      <div class="frm"><button class="btn" type="submit">ذخیره‌ی تغییرات</button></div>
    </form>
  </x-card>
  <x-card title="اتصال به کوییز ۲۴">
    <x-tag>متصل</x-tag>
    <dl class="dl" style="margin-top:12px"><dt>نام کاربری</dt><dd dir="ltr" style="text-align:end">{{ $user['quiz24'] }}</dd><dt>کد دانش‌آموزی</dt><dd dir="ltr" style="text-align:end">{{ $user['code'] }}</dd></dl>
    <div class="info" style="margin-top:12px">ورود به آزمون‌های کوییز ۲۴ فقط با ثبت‌نام در سایت آکادمی شیخان ممکن است.</div>
  </x-card>
</div>
<x-card title="نتایج آزمون‌های کوییز ۲۴" id="quiz24">
  <x-slot:actions><x-tag tone="d">همگام‌سازی خودکار</x-tag></x-slot:actions>
  <div class="tw"><table class="tbl"><tr><th>آزمون</th><th>درس</th><th>تاریخ</th><th>نمره</th><th>رتبه</th><th></th></tr>
  @foreach($done as $e)<tr><td><b>{{ $e['title'] }}</b></td><td>{{ $e['course'] }}</td><td>{{ $e['date'] }}</td><td>{{ fa($e['score']) }}</td><td>{{ fa($e['rank']) }} از {{ fa($e['of']) }}</td><td><a class="btn s g" href="{{ route('student.exams.show', $e['id']) }}">جزئیات</a></td></tr>@endforeach
  </table></div>
</x-card>
@endsection
