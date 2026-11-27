@extends('student.layouts.app')
@section('title', 'دستاوردها')
@section('content')
@php
 $perfectExam = ($results ?? collect())->contains(fn ($result) => $result->max_score > 0 && $result->score >= $result->max_score);
 $completedCourse = ($course_progress ?? collect())->first(fn ($course) => (int) data_get($course, 'progress_percent', 0) >= 100);
 $fullAttendance = $attendance_rate !== null && $attendance_rate >= 100;
 $strongResults = ($results ?? collect())->filter(fn ($result) => $result->max_score > 0 && ($result->score / $result->max_score) >= .9)->count();
@endphp
<section class="page" id="achievements">
<x-portal.page-head icon="chart" tone="tracking" title="دستاوردها"/>
<div class="card"><div class="chead"><h2>دستاوردها</h2><span class="pill">{{ collect([$perfectExam, $completedCourse !== null, $fullAttendance, $strongResults > 0])->filter()->count() }} از ۴</span></div>
 <div class="g3">
  <div class="card medal {{ $strongResults ? '' : 'off' }}"><div class="pl"><svg class="i"><use href="#i-trophy"/></svg></div><b>عملکرد درخشان</b><p style="font-size:13px;color:var(--muted)">{{ $strongResults ? $strongResults.' نتیجه با امتیاز ۹۰٪ یا بیشتر' : 'هنوز نتیجه‌ای در این بازه ثبت نشده' }}</p></div>
  <div class="card medal {{ $fullAttendance ? '' : 'off' }}"><div class="pl"><svg class="i"><use href="#i-cal"/></svg></div><b>حضور کامل</b><p style="font-size:13px;color:var(--muted)">{{ $attendance_rate === null ? 'داده حضور ثبت نشده' : 'نرخ حضور '.$attendance_rate.'٪' }}</p></div>
  <div class="card medal {{ $perfectExam ? '' : 'off' }}"><div class="pl"><svg class="i"><use href="#i-exam"/></svg></div><b>نمره کامل آزمون</b><p style="font-size:13px;color:var(--muted)">{{ $perfectExam ? 'حداقل یک نتیجه با نمره کامل' : 'هنوز نمره کامل ثبت نشده' }}</p></div>
  <div class="card medal {{ $completedCourse ? '' : 'off' }}"><div class="pl"><svg class="i"><use href="#i-book"/></svg></div><b>تکمیل یک دوره</b><p style="font-size:13px;color:var(--muted)">{{ $completedCourse ? data_get($completedCourse, 'title') : 'هنوز دوره‌ای به پایان نرسیده' }}</p></div>
 </div></div></section>
@endsection
