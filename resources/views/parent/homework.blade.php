@extends('parent.layouts.app')

@section('title', 'تکالیف')

@section('content')
@php $t = $hwTotals ?? []; @endphp
<div class="parent-page-heading"><div><span class="parent-eyebrow">پیگیری تکالیف</span><h1>تکالیف {{ data_get($kid ?? null, 'name') }}</h1><p>وضعیت تکالیف منتشرشده و روند ارسال شش ماه اخیر</p></div></div>
@include('parent.partials.kid-picker')
<div class="g g2">
    <section class="c"><h3>وضعیت کل تکالیف</h3>
        @include('parent.partials.chart', [
            'type' => 'donut',
            'parts' => [
                ['l' => 'به‌موقع', 'v' => (int) ($t['on_time'] ?? 0), 'c' => '--gn'],
                ['l' => 'با تأخیر', 'v' => (int) ($t['late'] ?? 0), 'c' => '--am'],
                ['l' => 'ارسال‌نشده', 'v' => (int) ($t['missing'] ?? 0), 'c' => '--rd'],
            ],
            'big' => $t['on_time'] ?? 0, 'small' => 'تکلیف به‌موقع',
        ])
    </section>

    <section class="c"><div class="parent-section-heading"><div><h3>وضعیت فعلی</h3><p>{{ $homeworkPaginator->total() }} تکلیف</p></div></div>
        {{-- tone: am | gn | bl | rd --}}
        @forelse (($homework ?? []) as $h)
            <div class="row"><div>{{ data_get($h, 'title') }}<small>مهلت: {{ data_get($h, 'due') }}</small></div><i class="bd {{ data_get($h, 'tone', 'am') }}">{{ data_get($h, 'status') }}</i></div>
        @empty
            <div class="parent-empty-state"><strong>تکلیفی برای این فرزند ثبت نشده است.</strong><span>تکالیف جدید پس از انتشار در این قسمت دیده می‌شوند.</span></div>
        @endforelse
        <div class="parent-pagination">{{ $homeworkPaginator->links() }}</div>
    </section>
</div>
@endsection
