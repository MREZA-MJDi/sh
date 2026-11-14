@extends('admin.layouts.app')

@section('page-title', 'مدیریت تیزر دوره')
@section('page-description', $course->title)

@section('content')
    <div class="panel-page-head" data-tone="learning">
        <div><h2>تیزر عمومی دوره</h2><p>{{ $course->title }}</p></div>
        <a class="panel-btn panel-btn-secondary" href="{{ route('admin.courses.index') }}">بازگشت به دوره‌ها</a>
    </div>
    @include('admin.courses._teaser_box', ['course' => $course])
@endsection
