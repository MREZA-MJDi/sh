@extends('student.layouts.app')
@section('title', 'جزئیات آزمون')
@section('content')
<section class="page"><div class="card"><h2>{{ $exam->title }}</h2>
 <p>{{ $exam->description }}</p>
 <div class="row"><div>درس / کلاس<small>{{ $exam->course?->title ?? $exam->classroom?->name }}</small></div></div>
 <div class="row"><div>تعداد سؤال<small>{{ $exam->questions->count() }}</small></div></div>
 <div class="row"><div>مدت آزمون<small>{{ $exam->duration_minutes ? $exam->duration_minutes.' دقیقه' : 'بدون محدودیت زمانی' }}</small></div></div>
 <div class="row"><div>دفعات مجاز<small>{{ $exam->attempts_allowed }}</small></div></div>
 <form method="POST" action="{{ route('student.exams.start', $exam) }}">@csrf<button class="btn s" type="submit">شروع آزمون / ادامه آزمون</button></form>
 <a class="pill" href="{{ route('student.exams.index') }}">بازگشت</a>
</div></section>
@endsection