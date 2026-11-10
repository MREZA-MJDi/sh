@extends('layouts.app')
@section('title', 'حضور و غیاب')
@section('content')
@php $at = $m['attendance']; $lead = $at['days'][0]['wd']; @endphp
<div class="info">حضور و غیاب به‌صورت <b>خودکار از اسکای‌روم</b> ثبت می‌شود؛ زمان ورود و مدت حضور در جلسه محاسبه می‌شود.@if($role === 'parent') (فرزند: {{ $child['name'] }})@endif</div>
<div class="stats">
  <x-stat tone="s1" icon="cal" label="درصد حضور" :value="fa($at['percent']).'٪'" :hint="$at['month']"/>
  <x-stat tone="s2" icon="check" label="حاضر" :value="fa($at['present'])" hint="جلسه"/>
  <x-stat tone="s3" icon="clock" label="تأخیر" :value="fa($at['late'])" hint="جلسه"/>
  <x-stat tone="s4" icon="user" label="غایب" :value="fa($at['absent'])" hint="جلسه"/>
</div>
<div class="grid2">
  <x-card title="تقویم {{ $at['month'] }}">
    <div class="cal">
      @foreach(['ش','ی','د','س','چ','پ','ج'] as $h)<span class="h">{{ $h }}</span>@endforeach
      @for($i = 0; $i < $lead; $i++)<b class="e"></b>@endfor
      @foreach($at['days'] as $d)<b class="{{ $d['s'] === 'p' ? '' : $d['s'] }}">{{ fa($d['d']) }}</b>@endforeach
    </div>
    <div class="legend"><span><i style="background:rgba(63,142,156,.6)"></i>حاضر</span><span><i style="background:rgba(214,160,70,.7)"></i>تأخیر</span><span><i style="background:rgba(190,100,100,.7)"></i>غایب</span><span><i style="background:rgba(255,255,255,.4)"></i>جمعه / آینده</span></div>
  </x-card>
  <x-card title="آخرین جلسات">
    @foreach($at['records'] as $r)
      <div class="trk"><div><b>{{ $r['course'] }}</b><span class="mu">{{ $r['date'] }} · ورود {{ $r['joined'] }} · {{ $r['stay'] }}</span></div><x-status :s="$r['status'] === 'late' ? 'tardy' : $r['status']"/></div>
    @endforeach
    <p class="mu" style="margin-top:8px">منبع: اسکای‌روم</p>
  </x-card>
</div>
@endsection
