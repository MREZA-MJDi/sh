@extends('parent.layouts.app')
@section('title', 'آزمون‌ها')
@section('content')
@php $subj = collect($subjects ?? []); @endphp
<div class="parent-page-heading"><div><span class="parent-eyebrow">ارزیابی آموزشی</span><h1>آزمون‌ها و نمرات</h1><p>نتایج ثبت‌شده و قابل مشاهده برای فرزند انتخاب‌شده</p></div></div>
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
            <div class="row"><div>{{ $result->exam?->title }}<small>{{ $result->exam?->course?->title ?? $result->exam?->classroom?->name }} · {{ data_get($resultDates ?? [], $result->id, '—') }}</small></div>
                <i class="bd pk">{{ $result->score }} از {{ $result->max_score }}</i>
            </div>
        @empty
            <x-portal.empty-state icon="file" tone="tracking" title="هنوز نتیجه‌ای برای نمایش وجود ندارد." description="نتایج پس از ثبت و تأیید مدرس در این بخش قرار می‌گیرند."/>
        @endforelse
        <div class="parent-pagination">{{ $results->links() }}</div>
    </section>
</div>
@endsection
