@extends('student.layouts.app')
@section('title', 'حضور و غیاب')
@section('content')
<section class="page" id="attendance"><div class="card">
 <div class="chead"><h2>حضور و غیاب · {{ $monthTitle }}</h2><span class="tag">حضور: {{ $attendance_rate === null ? '—' : $attendance_rate.'٪' }}</span></div>
 <p>{{ $attendance_count }} جلسه دارای وضعیت ثبت‌شده</p>
 <div class="cal sh-calendar-grid">
  <b>ش</b><b>ی</b><b>د</b><b>س</b><b>چ</b><b>پ</b><b>ج</b>
  @for ($i = 0; $i < $monthOffset; $i++)<b class="n"></b>@endfor
  @foreach ($calendar as $day)
   @if ($day['status'] === 'present')
    <b>{{ $day['day'] }}<small>حاضر</small></b>
   @elseif ($day['status'] === 'absent')
    <b class="a">{{ $day['day'] }}<small>غایب</small></b>
   @elseif ($day['status'] === 'late')
    <b style="color:var(--am)">{{ $day['day'] }}<small>تأخیر</small></b>
   @else
    <b class="n">{{ $day['day'] }}</b>
   @endif
  @endforeach
 </div>
</div></section>
@endsection
