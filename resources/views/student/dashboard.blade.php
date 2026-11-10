@extends('layouts.app')
@section('title', 'خانه یادگیری')
@section('content')
@php
$today = collect($m['schedule'])->where('day', 'امروز');
$open = collect($m['assignments'])->whereIn('status', ['pending', 'late']);
$soonHw = $open->where('soon', true)->count();
$exams = collect($m['exams'])->whereIn('status', ['ready', 'upcoming']);
$course = fn ($id) => collect($m['courses'])->firstWhere('id', $id);
@endphp
<div class="hero">
  <svg class="av" viewBox="0 0 120 120" aria-hidden="true"><use href="#avatar"/></svg>
  <div class="txt"><h2>عصر بخیر، {{ explode(' ', $user['name'])[0] }}!</h2><p>امروز {{ fa($today->count()) }} کلاس و {{ fa($soonHw) }} تکلیف با مهلت فردا داری.</p>
    <a class="btn" href="{{ route('student.classes.index') }}">ورود به کلاس آنلاین</a><a class="btn g" href="{{ route('student.assignments.index') }}">تکالیف من</a></div>
</div>
<div class="stats">
  <x-stat tone="s1" icon="video" label="کلاس‌های امروز" :value="fa($today->count())" :hint="'اولی ساعت '.$today->first()['time']" :href="route('student.classes.index')"/>
  <x-stat tone="s2" icon="task" label="تکالیف باز" :value="fa($open->count())" :hint="fa($soonHw).' مورد تا فردا'" :href="route('student.assignments.index')"/>
  <x-stat tone="s3" icon="exam" label="آزمون پیش‌رو" :value="fa($exams->count())" hint="در کوییز ۲۴" :href="route('student.exams.index')"/>
  <x-stat tone="s4" icon="bell" label="اخبار مهم" :value="fa(count($m['news']))" hint="از طرف ادمین" :href="route('student.news.index')"/>
</div>
<div class="row">
  <x-card title="کلاس‌های امروز"><x-slot:actions><a class="pill" href="{{ route('student.classes.index') }}">همه</a></x-slot:actions>
    @foreach($today as $s)<x-trk icon="video" :tone="$course($s['course_id'])['tone']" :title="$course($s['course_id'])['title'].' · '.$s['session']" :sub="$s['time'].' · '.$course($s['course_id'])['teacher']" :href="route('student.classes.index')"/>@endforeach
  </x-card>
  <x-card title="مهلت‌های نزدیک"><x-slot:actions><a class="pill" href="{{ route('student.assignments.index') }}">همه</a></x-slot:actions>
    @foreach($open->take(2) as $a)<x-trk icon="task" tone="c" :title="$a['title']" :sub="'مهلت: '.$a['due']" :href="route('student.assignments.show', $a['id'])"/>@endforeach
    @foreach($exams->take(1) as $e)<x-trk icon="exam" tone="b" :title="$e['title']" :sub="$e['date'].' · '.$e['time']" :href="route('student.exams.show', $e['id'])"/>@endforeach
  </x-card>
</div>
<div class="row">
  <x-card title="پیشرفت دوره‌ها"><x-slot:actions><a class="pill" href="{{ route('student.courses.index') }}">همه</a></x-slot:actions>
    @foreach($m['courses'] as $c)<div style="margin-bottom:10px"><div class="chead" style="margin:0"><b>{{ $c['title'] }}</b><span class="mu">{{ fa($c['progress']) }}٪</span></div><x-progress :value="$c['progress']"/></div>@endforeach
  </x-card>
  <x-card title="اخبار مهم"><x-slot:actions><a class="pill" href="{{ route('student.news.index') }}">همه</a></x-slot:actions>
    @foreach(array_slice($m['news'], 0, 2) as $n)<a class="news" href="{{ route('student.news.show', $n['id']) }}" style="display:block"><b>{{ $n['title'] }}</b><span class="mu">{{ $n['date'] }}</span></a>@endforeach
  </x-card>
</div>
@endsection
