@extends('layouts.portal-reference')

@section('title', 'کارتابل دانش‌آموز')

@section('content')
    @php
        $portalRole = 'student';
        $faNumber = static fn ($value, int $precision = 0): string =>
            \App\Support\PersianDate::toPersianDigits(number_format((float) $value, $precision, '٫', '٬'));
    @endphp

    @include('student.dashboard.sections.home')
    @include('student.dashboard.sections.news')
    @include('student.dashboard.sections.classes')
    @include('student.dashboard.sections.videos')
    @include('student.dashboard.sections.homework')
    @include('student.dashboard.sections.exams')
    @include('student.dashboard.sections.resources')
    @include('student.dashboard.sections.shop')
    @include('student.dashboard.sections.messages')
    @include('student.dashboard.sections.results')
    @include('student.dashboard.sections.attendance')
    @include('student.dashboard.sections.notes')
    @include('student.dashboard.sections.profile')
@endsection
