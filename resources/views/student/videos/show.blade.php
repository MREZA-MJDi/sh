@extends('layouts.app')
@section('title', $course['title'] . ' · ' . $session['title'])
@section('content')
@php
$vids = collect($course['sessions'])->where('recorded', true);
$res = collect($m['resources'])->where('course_id', $course['id']);
@endphp
<x-crumbs :items="['ویدیوها' => route('student.videos.index', ['course' => $course['id']]), $course['title'] => null, $session['title'] => null]"/>
<div class="grid2">
  <div class="stack">
    <div class="player" data-player data-total="{{ $session['seconds'] }}">
      <div class="screen">
        <span class="wm" data-wm>{{ $user['phone'] }} · {{ $user['name'] }}</span>
        <div><x-icon name="play"/><b>پخش‌کننده‌ی اسپات پلیر</b><span>در نسخه‌ی نهایی، این بخش با SDK و لایسنس اسپات پلیر جایگزین می‌شود.</span></div>
      </div>
      <div class="ctrl"><button type="button" data-play aria-label="پخش / توقف">▶</button><div class="bar" aria-label="پیشرفت ویدیو"><i></i></div><span data-time>۰۰:۰۰ / ۰۰:۰۰</span>
        <select data-speed aria-label="سرعت پخش"><option value="1">۱×</option><option value="1.5">۱٫۵×</option><option value="2">۲×</option></select></div>
    </div>
    <x-card :title="$session['title'].' · '.$session['topic']">
      <dl class="dl"><dt>درس</dt><dd>{{ $course['title'] }}</dd><dt>مدرس</dt><dd>{{ $course['teacher'] }}</dd><dt>تاریخ</dt><dd>{{ $session['date'] }}</dd><dt>مدت</dt><dd>{{ $session['duration'] }}</dd></dl>
      <div class="info" style="margin-top:12px">ویدیو روی سرور آکادمی نگهداری می‌شود و فقط با لایسنس اسپات پلیر مخصوص حساب شما پخش می‌شود. نام و موبایل شما روی تصویر نمایش داده می‌شود.</div>
    </x-card>
    <x-card title="منابع این درس">
      @forelse($res as $r)<x-trk icon="file" tone="c" :title="$r['title']" :sub="$r['size']"><a class="btn s g" href="#"><x-icon name="download"/>دانلود</a></x-trk>@empty<div class="empty">منبعی ثبت نشده است.</div>@endforelse
    </x-card>
  </div>
  <x-card title="جلسات این کلاس">
    @foreach($vids->reverse() as $s)
      <a class="trk" href="{{ route('student.videos.show', [$course['id'], $s['n']]) }}" @if($s['n'] === $session['n']) aria-current="true" style="font-weight:700" @endif>
        <div class="th t-{{ $course['tone'] }}" style="width:38px;height:38px"><x-icon name="play" style="width:16px;height:16px"/></div>
        <div><b>{{ $s['title'] }}</b><span class="mu">{{ $s['topic'] }} · {{ $s['duration'] }}</span></div>
      </a>
    @endforeach
  </x-card>
</div>
@endsection
