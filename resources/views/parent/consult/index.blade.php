@extends('parent.layouts.app')
@section('title', 'درخواست‌های مشاوره')
@section('content')
@php $kids = $children; $kid = $selected_child; @endphp
<h2>درخواست‌های مشاوره</h2>
@include('parent.partials.kid-picker')
<section class="c">
    <div class="chead"><h3>درخواست‌های ثبت‌شده</h3><a class="b s" href="{{ route('guardian.consultations.create', ['child' => $selected_child?->id]) }}">درخواست مشاوره جدید</a></div>
    @forelse ($consultations as $consultation)
        <a class="row" href="{{ route('guardian.consultations.show', $consultation) }}">
            <div>{{ $consultation->topic }}<small>{{ $consultation->student?->name }} · {{ $consultation->created_at?->format('Y/m/d H:i') }}</small></div>
            @php $labels = ['pending'=>'در انتظار بررسی','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده']; @endphp
            <i class="bd {{ $consultation->status === 'done' ? 'gn' : 'am' }}">{{ $labels[$consultation->status] ?? $consultation->status }}</i>
        </a>
    @empty
        <p>درخواستی برای این فرزند ثبت نشده است.</p>
    @endforelse
    {{ $consultations->links() }}
</section>
@endsection
