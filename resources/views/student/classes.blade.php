@extends('layouts.app')
@section('title', 'کلاس‌های من')
@section('content')
<div class="info">کلاس‌ها در <b>اسکای‌روم</b> برگزار می‌شوند و حضور شما هنگام ورود به‌صورت خودکار ثبت می‌شود. فقط کلاس‌های ثبت‌نام‌شده نمایش داده می‌شود.</div>
<x-card title="برنامه‌ی پیش‌رو">
  @foreach($m['schedule'] as $s)
    @php $c = collect($m['courses'])->firstWhere('id', $s['course_id']); @endphp
    <x-trk icon="video" :tone="$c['tone']" :title="$c['title'].' · '.$s['session']" :sub="$s['day'].' · '.$s['time'].' · '.$c['teacher']">
      @if($s['live'])<a class="btn s" href="{{ $c['skyroom'] }}" target="_blank" rel="noopener">ورود به اسکای‌روم</a>
      @elseif($s['day'] === 'امروز')<x-tag tone="w">به‌زودی</x-tag>
      @else<x-tag tone="d">{{ $s['day'] }}</x-tag>@endif
    </x-trk>
  @endforeach
</x-card>
<x-card title="دوره‌های ثبت‌نام‌شده">
  <div class="tw"><table class="tbl"><tr><th>دوره</th><th>مدرس</th><th>زمان هفتگی</th><th></th></tr>
  @foreach($m['courses'] as $c)<tr><td><b>{{ $c['title'] }}</b></td><td>{{ $c['teacher'] }}</td><td>{{ $c['schedule'] }}</td><td><a class="btn s g" href="{{ route('student.courses.show', $c['id']) }}">جزئیات دوره</a></td></tr>@endforeach
  </table></div>
</x-card>
@endsection
