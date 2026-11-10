@extends('layouts.portal-reference')

@section('title', 'کارتابل اولیاء')

@section('content')
    @php
        $faNumber = static fn ($value, int $precision = 0): string =>
            \App\Support\PersianDate::toPersianDigits(number_format((float) $value, $precision, '٫', '٬'));
    @endphp

    @include('guardian.dashboard.sections.home')
    @include('guardian.dashboard.sections.news')
    @include('guardian.dashboard.sections.progress')
    @include('guardian.dashboard.sections.homework')
    @include('guardian.dashboard.sections.exams')
    @include('guardian.dashboard.sections.attendance')
    @include('guardian.dashboard.sections.resources')
    @include('guardian.dashboard.sections.consult')
    @include('guardian.dashboard.sections.messages')
@endsection
