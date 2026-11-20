@extends('parent.layouts.app')
@section('title', 'آزمون‌ها')
@section('content')
@php $subj = collect($subjects ?? []); @endphp
<h2>آزمون‌ها</h2>
@include('parent.partials.kid-picker')
<div class="g g2">
    <section class="c"><h3>نمره ثبت‌شده به تفکیک درس</h3>
        @include('parent.partials.chart', [
            'type' => 'bar',
            'labels' => $subj->map(fn ($s) => data_get($s, 'name'))->values()->all(),
            'series' => [
                ['name' => data_get($kid ?? null, 'name', ''), 'c' => '--pri', 'data' => $subj->map(fn ($s) => data_get($s, 'score'))->values()->all()],
            ],
        ])
    </section>
    <section class="c"><div class="parent-section-heading"><div><h3>نتایج قابل مشاهده</h3><p>{{ $results->total() }} نتیجه</p></div></div>
        @forelse (($results ?? collect()) as $result)
            <div class="row"><div>{{ $result->exam?->title }}<small>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name }} · {{ $result->created_at?->format('Y/m/d') }}</small></div>
                <i class="bd pk">{{ $result->score }} از {{ $result->max_score }}</i>
            </div>
        @empty
            <div class="parent-empty-state"><strong>هنوز نتیجه‌ای برای نمایش وجود ندارد.</strong><span>نتایج پس از ثبت و تأیید مدرس در این بخش قرار می‌گیرند.</span></div>
        @endforelse
        <div class="parent-pagination">{{ $results->links() }}</div>
    </section>
</div>
@endsection
