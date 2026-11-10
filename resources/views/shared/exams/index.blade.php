@extends('layouts.app')
@section('title', $role === 'parent' ? 'نتایج آزمون‌ها' : 'آزمون‌ها')
@section('content')
<div class="info">آزمون‌ها روی <b>کوییز ۲۴</b> برگزار می‌شوند. ورود به آزمون‌ها فقط با ثبت‌نام در سایت آکادمی شیخان ممکن است و نتیجه‌ی هر آزمون به‌صورت خودکار {{ $role === 'student' ? 'در پروفایل شما' : 'برای فرزند شما' }} ثبت می‌شود.</div>
<x-card title="فهرست آزمون‌ها">
  <x-slot:actions><div class="tabs" data-tabs data-target="#ex-list"><button class="on" data-f="all">همه</button><button data-f="ready">آماده‌ی شروع</button><button data-f="upcoming">به‌زودی</button><button data-f="done">برگزارشده</button></div></x-slot:actions>
  <div class="tw" id="ex-list"><table class="tbl">
    <tr><th>آزمون</th><th>درس</th><th>زمان</th><th>مدت</th><th>وضعیت</th><th>نتیجه</th><th></th></tr>
    @foreach($m['exams'] as $e)
      <tr data-item data-cat="{{ $e['status'] }}"><td><b>{{ $e['title'] }}</b></td><td>{{ $e['course'] }}</td><td>{{ $e['date'] }} · {{ $e['time'] }}</td><td>{{ fa($e['duration']) }} دقیقه</td><td><x-status :s="$e['status']"/></td>
        <td>@if($e['score'] !== null){{ fa($e['score']) }} · رتبه‌ی {{ fa($e['rank']) }}@else<span class="mu">—</span>@endif</td>
        <td><a class="btn s g" href="{{ $rt('exams.show', $e['id']) }}">جزئیات</a></td></tr>
    @endforeach
  </table></div>
</x-card>
@endsection
