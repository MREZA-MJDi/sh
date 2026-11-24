@extends('student.layouts.app')
@section('title', 'جزئیات آزمون')
@section('content')
<section class="page">
 <x-portal.page-head :title="$exam->title" icon="check" tone="tracking" :back="route('student.exams.index')"><x-portal.status domain="exam" :value="$exam->status"/></x-portal.page-head>
 <div class="card">
  <h2>{{ $exam->course?->title ?? $exam->classroom?->name ?? 'آزمون آموزشی' }}</h2>
  @if($exam->description)<p>{{ $exam->description }}</p>@endif
  <div class="portal-metrics">
   <div class="portal-metric"><small>تعداد سؤال</small><strong>{{ $exam->questions->count() }}</strong></div>
   <div class="portal-metric"><small>مدت آزمون</small><strong>{{ $exam->duration_minutes ? $exam->duration_minutes.' دقیقه' : 'بدون محدودیت زمانی' }}</strong></div>
   <div class="portal-metric"><small>دفعات مجاز</small><strong>{{ $exam->attempts_allowed }}</strong></div>
  </div>
  <x-portal.exam-actions :exam="$exam" :details="true"/>
 </div>
</section>
@endsection
