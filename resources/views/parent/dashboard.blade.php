@extends('layouts.app')
@section('title', 'کارتابل اولیاء')
@section('content')
@php
$open = collect($m['assignments'])->whereIn('status', ['pending', 'late']);
$done = collect($m['exams'])->where('status', 'done');
@endphp
<div class="hero">
  <svg class="av" viewBox="0 0 120 120" aria-hidden="true"><use href="#avatar"/></svg>
  <div class="txt"><h2>سلام، {{ $user['name'] }}</h2><p>وضعیت <b>{{ $child['name'] }}</b> ({{ $child['grade'] }}) در یک نگاه.</p>
    <a class="btn" href="{{ route('parent.consult.create') }}">درخواست جلسه‌ی مشاوره</a><a class="btn g" href="{{ route('parent.messages.create') }}">ارسال پیام</a></div>
</div>
<div class="stats">
  <x-stat tone="s1" icon="book" label="میانگین پیشرفت" :value="fa(round(collect($m['courses'])->avg('progress'))).'٪'" :hint="fa(count($m['courses'])).' دوره‌ی فعال'" :href="route('parent.courses.index')"/>
  <x-stat tone="s2" icon="task" label="تکالیف باز" :value="fa($open->count())" :hint="fa($open->where('soon', true)->count()).' مورد تا فردا'" :href="route('parent.assignments.index')"/>
  <x-stat tone="s3" icon="exam" label="میانگین آزمون‌ها" :value="fa(str_replace('.', '٫', (string) round($done->avg('score'), 1)))" hint="از ۲۰ · کوییز ۲۴" :href="route('parent.exams.index')"/>
  <x-stat tone="s4" icon="cal" label="درصد حضور" :value="fa($m['attendance']['percent']).'٪'" hint="ثبت از اسکای‌روم" :href="route('parent.attendance.index')"/>
</div>
<div class="row">
  <x-card title="پیشرفت دوره‌ها"><x-slot:actions><a class="pill" href="{{ route('parent.courses.index') }}">همه</a></x-slot:actions>
    @foreach($m['courses'] as $c)<a href="{{ route('parent.courses.show', $c['id']) }}" style="display:block;margin-bottom:10px"><div class="chead" style="margin:0"><b>{{ $c['title'] }}</b><span class="mu">{{ fa($c['progress']) }}٪</span></div><x-progress :value="$c['progress']"/></a>@endforeach
  </x-card>
  <x-card title="تکالیف نیازمند توجه"><x-slot:actions><a class="pill" href="{{ route('parent.assignments.index') }}">همه</a></x-slot:actions>
    @forelse($open as $a)<x-trk icon="task" tone="c" :title="$a['title']" :sub="$a['course'].' · مهلت: '.$a['due']" :href="route('parent.assignments.show', $a['id'])"><x-status :s="$a['status']"/></x-trk>@empty<div class="empty">همه‌ی تکالیف تحویل شده است.</div>@endforelse
  </x-card>
</div>
<div class="row">
  <x-card title="آخرین نتایج آزمون"><x-slot:actions><a class="pill" href="{{ route('parent.exams.index') }}">همه</a></x-slot:actions>
    @foreach($done->take(3) as $e)<x-trk icon="exam" tone="b" :title="$e['title']" :sub="$e['date'].' · رتبه‌ی '.fa($e['rank']).' از '.fa($e['of'])" :href="route('parent.exams.show', $e['id'])"><b>{{ fa($e['score']) }}</b></x-trk>@endforeach
  </x-card>
  <x-card title="اخبار مهم"><x-slot:actions><a class="pill" href="{{ route('parent.news.index') }}">همه</a></x-slot:actions>
    @foreach(array_slice($m['news'], 0, 3) as $n)<a class="news" href="{{ route('parent.news.show', $n['id']) }}" style="display:block"><b>{{ $n['title'] }}</b><span class="mu">{{ $n['date'] }}</span></a>@endforeach
  </x-card>
</div>
@endsection
