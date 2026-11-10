@extends('layouts.app')
@section('title', 'ویدیوهای ضبط‌شده')
@section('content')
@php
$cid = (int) request('course', $m['courses'][0]['id']);
$course = collect($m['courses'])->firstWhere('id', $cid) ?? $m['courses'][0];
$vids = collect($course['sessions'])->where('recorded', true)->reverse();
@endphp
<div class="info">ویدیوها فقط از طریق <b>اسپات پلیر</b> قابل مشاهده‌اند و برای هر کلاس ثبت‌نام‌شده جداگانه نمایش داده می‌شوند.</div>
<x-card title="انتخاب کلاس">
  <div class="tabs">
    @foreach($m['courses'] as $c)<button type="button" @class(['on' => $c['id'] === $course['id']]) onclick="location.href='{{ route('student.videos.index', ['course' => $c['id']]) }}'">{{ $c['title'] }}</button>@endforeach
  </div>
</x-card>
<x-card :title="'ضبط جلسات · '.$course['title']">
  <x-slot:actions><x-tag>{{ fa($vids->count()) }} ویدیو</x-tag></x-slot:actions>
  @foreach($vids as $s)
    <x-trk icon="video" :tone="$course['tone']" :title="$s['title'].' · '.$s['topic']" :sub="$s['date'].' · '.$s['duration']" :href="route('student.videos.show', [$course['id'], $s['n']])"/>
  @endforeach
</x-card>
@endsection
