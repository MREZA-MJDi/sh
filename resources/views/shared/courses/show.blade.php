@extends('layouts.app')
@section('title', $course['title'])
@section('content')
@php
$hw = collect($m['assignments'])->where('course_id', $course['id']);
$res = collect($m['resources'])->where('course_id', $course['id']);
$ex = collect($m['exams'])->where('course_id', $course['id']);
$att = collect($course['sessions'])->where('recorded', true);
$attPct = $att->count() ? round($att->where('attended', true)->count() / $att->count() * 100) : 0;
@endphp
<x-crumbs :items="[($role === 'parent' ? 'پیشرفت دوره‌ها' : 'دوره‌های من') => $rt('courses.index'), $course['title'] => null]"/>
<div class="stats">
  <x-stat tone="s1" icon="book" label="پیشرفت دوره" :value="fa($course['progress']).'٪'" :hint="fa($course['done']).' از '.fa($course['total']).' جلسه'"/>
  <x-stat tone="s2" icon="cal" label="حضور در جلسات" :value="fa($attPct).'٪'" hint="ثبت خودکار از اسکای‌روم"/>
  <x-stat tone="s3" icon="task" label="تکالیف" :value="fa($hw->count())" :hint="fa($hw->where('status','graded')->count()).' مورد بررسی‌شده'"/>
  <x-stat tone="s4" icon="exam" label="آزمون‌ها" :value="fa($ex->count())" hint="در کوییز ۲۴"/>
</div>
<div class="grid2">
  <div class="stack">
    <x-card title="درباره‌ی دوره">
      <p style="margin-bottom:12px">{{ $course['desc'] }}</p>
      <dl class="dl"><dt>مدرس</dt><dd>{{ $course['teacher'] }}</dd><dt>برنامه‌ی هفتگی</dt><dd>{{ $course['schedule'] }}</dd><dt>محل برگزاری</dt><dd>اسکای‌روم (آنلاین)</dd></dl>
      <x-progress :value="$course['progress']"/>
      @if($role === 'student')<div class="frm"><a class="btn" href="{{ $course['skyroom'] }}" target="_blank" rel="noopener">ورود به کلاس در اسکای‌روم</a><a class="btn g" href="{{ route('student.videos.index', ['course' => $course['id']]) }}">ویدیوهای ضبط‌شده</a></div>@endif
    </x-card>
    <x-card title="جلسات">
      <div class="tw"><table class="tbl"><tr><th>جلسه</th><th>موضوع</th><th>تاریخ</th><th>حضور</th><th></th></tr>
      @foreach($course['sessions'] as $s)
        <tr><td>{{ $s['title'] }}</td><td>{{ $s['topic'] }}</td><td>{{ $s['date'] }}</td>
          <td>@if($s['recorded'])<x-status :s="$s['attended'] ? 'present' : 'absent'"/>@else<span class="mu">—</span>@endif</td>
          <td>@if($role === 'student' && $s['recorded'])<a class="btn s g" href="{{ route('student.videos.show', [$course['id'], $s['n']]) }}">ویدیو</a>@endif</td></tr>
      @endforeach</table></div>
    </x-card>
  </div>
  <div class="stack">
    <x-card title="تکالیف این دوره">
      @forelse($hw as $a)
        <x-trk icon="task" tone="{{ $course['tone'] }}" :title="$a['title']" :sub="'مهلت: '.$a['due']" :href="$rt('assignments.show', $a['id'])"><x-status :s="$a['status']"/></x-trk>
      @empty<div class="empty">تکلیفی ثبت نشده است.</div>@endforelse
    </x-card>
    <x-card title="منابع این دوره">
      @forelse($res as $r)
        <x-trk icon="file" tone="c" :title="$r['title']" :sub="$r['size']"/>
      @empty<div class="empty">منبعی ثبت نشده است.</div>@endforelse
    </x-card>
  </div>
</div>
@endsection
