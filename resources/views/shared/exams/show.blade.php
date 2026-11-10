@extends('layouts.app')
@section('title', $e['title'])
@section('content')
<x-crumbs :items="['آزمون‌ها' => $rt('exams.index'), $e['title'] => null]"/>
<div class="grid2">
  <div class="stack">
    <x-card title="مشخصات آزمون">
      <x-slot:actions><x-status :s="$e['status']"/></x-slot:actions>
      <dl class="dl"><dt>درس</dt><dd>{{ $e['course'] }}</dd><dt>زمان برگزاری</dt><dd>{{ $e['date'] }} · ساعت {{ $e['time'] }}</dd><dt>مدت</dt><dd>{{ fa($e['duration']) }} دقیقه</dd><dt>تعداد سؤال</dt><dd>{{ fa($e['questions']) }}</dd><dt>سامانه‌ی برگزاری</dt><dd>کوییز ۲۴</dd></dl>
      <h3 style="margin:16px 0 6px">مباحث آزمون</h3>
      @foreach($e['topics'] as $t)<x-tag>{{ $t }}</x-tag> @endforeach
    </x-card>
    @if($e['status'] === 'done')
      <x-card title="نتیجه‌ی آزمون">
        <div class="stats" style="grid-template-columns:repeat(3,1fr)">
          <x-stat tone="s1" icon="chart" label="نمره" :value="fa($e['score'])" hint="از ۲۰"/>
          <x-stat tone="s2" icon="users" label="رتبه" :value="fa($e['rank'])" :hint="'از '.fa($e['of']).' نفر'"/>
          <x-stat tone="s3" icon="clock" label="مدت" :value="fa($e['duration'])" hint="دقیقه"/>
        </div>
      </x-card>
    @endif
  </div>
  <x-card title="{{ $e['status'] === 'done' ? 'کوییز ۲۴' : 'شرکت در آزمون' }}">
    @if($role === 'student' && $e['status'] === 'ready')
      <div class="info w" style="margin-bottom:12px">قبل از شروع، مطمئن شوید با همین حساب در سایت آکادمی شیخان ثبت‌نام کرده‌اید؛ بدون ثبت‌نام ورود به کوییز ۲۴ ممکن نیست.</div>
      <a class="btn" href="{{ $e['quiz24_url'] }}" target="_blank" rel="noopener"><x-icon name="link"/>ورود به کوییز ۲۴</a>
    @elseif($e['status'] === 'upcoming')
      <p class="mu">دکمه‌ی ورود پیش از زمان آزمون فعال می‌شود.</p>
    @elseif($e['status'] === 'done')
      <p class="mu" style="margin-bottom:10px">نتیجه از کوییز ۲۴ همگام‌سازی شده است.</p><a class="btn g s" href="{{ $e['quiz24_url'] }}" target="_blank" rel="noopener">مشاهده در کوییز ۲۴</a>
    @else
      <p class="mu">شرکت در آزمون توسط دانش‌آموز انجام می‌شود.</p>
    @endif
  </x-card>
</div>
@endsection
