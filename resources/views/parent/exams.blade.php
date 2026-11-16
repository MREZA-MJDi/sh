@extends('parent.layouts.app')

@section('title', 'آزمون‌ها')

@section('content')
@php $subj = collect($subjects ?? []); @endphp
<h2>آزمون‌ها</h2>
@include('parent.partials.kid-picker')
<div class="g g2">
    <section class="c"><h3>نمره هر درس در برابر میانگین کلاس</h3>
        @include('parent.partials.chart', [
            'type' => 'bar',
            'labels' => $subj->map(fn ($s) => data_get($s, 'name'))->values()->all(),
            'series' => [
                ['name' => data_get($kid ?? null, 'name', ''), 'c' => '--pri', 'data' => $subj->map(fn ($s) => data_get($s, 'score'))->values()->all()],
                ['name' => 'میانگین کلاس', 'c' => '--am', 'data' => $subj->map(fn ($s) => data_get($s, 'class_avg'))->values()->all()],
            ],
        ])
    </section>

    <section class="c"><h3>نتایج</h3>
        @forelse ($subj as $s)
            @php $score = (float) data_get($s, 'score', 0); $avg = (float) data_get($s, 'class_avg', 0); @endphp
            <div class="row"><div>{{ data_get($s, 'name') }}<small>میانگین کلاس {{ data_get($s, 'class_avg') }}</small></div>
                <div class="pr"><u class="{{ $score >= $avg ? 'gn' : 'am' }}" style="width:{{ min(100, round($score * 5)) }}%"></u></div>
                <i class="bd pk">{{ data_get($s, 'score') }} از ۲۰</i>
            </div>
        @empty
            <p>نتیجه‌ای ثبت نشده است.</p>
        @endforelse
    </section>
</div>
@endsection
