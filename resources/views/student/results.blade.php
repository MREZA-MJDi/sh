@extends('student.layouts.app')
@section('title', 'نمرات و عملکرد')
@section('content')
@php
 $chartTones = [
  'linear-gradient(180deg,#8cc6d6,#5db0b4)',
  'linear-gradient(180deg,#5b8fc0,#3f86c4)',
  'linear-gradient(180deg,#5db0b4,#2f7483)',
  'linear-gradient(180deg,#9b9acb,#4a4b8a)',
 ];
@endphp
<section class="page" id="results">
 <div class="stats">
  <div class="st s1"><div class="ic"><svg class="i"><use href="#i-chart"/></svg></div><small>میانگین نمرات</small><b>{{ $averageScore === null ? '—' : number_format($averageScore, 1) }}</b><span>از ۲۰</span></div>
  <div class="st s2"><div class="ic"><svg class="i"><use href="#i-trophy"/></svg></div><small>بالاترین نمره</small><b>{{ $highestScore === null ? '—' : number_format($highestScore, 1) }}</b><span>از ۲۰</span></div>
  <div class="st s3"><div class="ic"><svg class="i"><use href="#i-exam"/></svg></div><small>نتایج ثبت‌شده</small><b>{{ $results->count() }}</b><span>آزمون</span></div>
  <div class="st s4"><div class="ic"><svg class="i"><use href="#i-task"/></svg></div><small>تکالیف ارسال‌شده</small><b>{{ $submittedHomeworkCount }}</b><span>در کلاس‌های فعال</span></div>
 </div>
 <div class="row">
  <div class="card"><div class="chead"><h2>میانگین نمره‌ی هر درس</h2></div>
   <div class="bars"><div class="cols">
    @forelse ($courseScores as $course)
     <div class="col"><div style="height:{{ min(100, max(0, round($course['score'] / 20 * 100))) }}%;background:{{ $chartTones[$loop->index % count($chartTones)] }}"></div>{{ $course['name'] }}</div>
    @empty
     <p>هنوز نمره‌ای برای نمایش وجود ندارد.</p>
    @endforelse
   </div></div>
  </div>
  <div class="card"><div class="chead"><h2>آخرین نمرات</h2></div>
   <div class="tw"><table class="tbl"><tr><th>آزمون</th><th>درس</th><th>نمره</th></tr>
    @forelse ($results as $result)
     <tr><td>{{ $result->exam?->title ?? 'آزمون' }}</td><td>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name ?? '—' }}</td><td>{{ $result->score === null ? '—' : $result->score.' / '.$result->max_score }}</td></tr>
    @empty
     <tr><td colspan="3">نتیجه‌ای ثبت نشده است.</td></tr>
    @endforelse
   </table></div>
  </div>
 </div>
</section>
@endsection
