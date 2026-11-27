@extends('student.layouts.app')
@section('title', 'حضور و غیاب')
@section('content')
<section class="page" id="attendance">
<x-portal.page-head icon="calendar" tone="tracking" title="حضور و غیاب"/>

 <div class="card">
  <div class="chead">
   <h2>خلاصه ماه</h2>
   <span class="tag">حضور: {{ $attendance_rate === null ? '—' : $attendance_rate.'٪' }}</span>
  </div>
  <form class="attendance-month-filter" method="GET" action="{{ route('student.attendance') }}">
   <label for="attendance-month">ماه آموزشی</label>
   <select id="attendance-month" class="fld" name="month">
    @foreach($monthOptions as $option)
     <option value="{{ $option }}" @selected($option === $monthKey)>{{ str_replace('-', '/', $option) }}</option>
    @endforeach
   </select>
   <button class="btn s" type="submit">نمایش</button>
  </form>
  <div class="attendance-summary">
   @foreach(['present'=>['حاضر','gn'], 'absent'=>['غایب','rd'], 'late'=>['تأخیر','am'], 'excused'=>['غیبت موجه','bl']] as $status => [$label,$tone])
    <div class="attendance-stat" data-tone="{{ $tone }}"><span>{{ $label }}</span><strong>{{ $counts->get($status, 0) }}</strong></div>
   @endforeach
  </div>
  <div class="chead"><h2>تقویم {{ $monthTitle }}</h2><span>{{ $attendance_count }} جلسه ثبت‌شده</span></div>
  <div class="cal sh-calendar-grid">
   @foreach(['ش','ی','د','س','چ','پ','ج'] as $weekday)<b class="attendance-weekday">{{ $weekday }}</b>@endforeach
   @for($i = 0; $i < $monthOffset; $i++)<b class="n" aria-hidden="true"></b>@endfor
   @foreach($calendar as $day)
    @php($labels = ['present'=>'حاضر','absent'=>'غایب','late'=>'تأخیر','excused'=>'غیبت موجه','none'=>'بدون سابقه'])
    <b class="{{ ['present'=>'p','absent'=>'a','late'=>'l','excused'=>'e','none'=>'n'][$day['status']] }}" aria-label="{{ $day['day'] }}: {{ $labels[$day['status']] }}">
     {{ $day['day'] }}@if($day['status'] !== 'none')<small>{{ $labels[$day['status']] }}</small>@endif
    </b>
   @endforeach
  </div>
 </div>
 <div class="card">
  <div class="chead"><h2>سوابق ثبت‌شده این ماه</h2></div>
  @forelse($monthRecords as $record)
   <div class="trk"><div><b>{{ $record->classroom?->name ?? 'کلاس آموزشی' }}</b><span>{{ \App\Support\PersianDate::format($record->session_date, 'Y/m/d') }}</span></div><x-portal.status domain="attendance" :value="$record->status"/></div>
  @empty
   <x-portal.empty-state icon="calendar" title="سابقه‌ای برای این ماه ثبت نشده است." description="پس از ثبت حضور توسط آموزشگاه، جزئیات در این بخش نمایش داده می‌شود."/>
  @endforelse
 </div>
</section>
@endsection
