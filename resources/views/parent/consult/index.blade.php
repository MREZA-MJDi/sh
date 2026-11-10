@extends('layouts.parent')

@section('title', 'درخواست‌های مشاوره')

@section('content')
    <x-crumbs :items="['کارتابل اولیا' => route('guardian.dashboard'), 'درخواست‌های مشاوره' => null]"/>

    <x-card title="درخواست‌های من">
        <x-slot:actions>
            <a class="btn s" href="{{ route('guardian.consultations.create') }}">درخواست جدید</a>
        </x-slot:actions>

        @forelse($consultations as $consultation)
            <a class="trk" href="{{ route('guardian.consultations.show', $consultation) }}">
                <div class="th t-{{ ['a','b','c','d'][$loop->index % 4] }}"><x-icon name="phone"/></div>
                <div>
                    <b>{{ $consultation->topic ?: 'درخواست مشاوره' }}</b>
                    <span class="mu">
                        {{ $consultation->student?->name ?? 'فرزند حذف‌شده' }}
                        · {{ $consultation->desired_at ? \App\Support\PersianDate::format($consultation->desired_at, 'Y/m/d H:i') : 'زمان پیشنهادی ثبت نشده' }}
                    </span>
                </div>
                @php($consultationStatus = ['approved' => 'scheduled', 'completed' => 'done'][$consultation->status] ?? $consultation->status)
                @if($consultationStatus === 'pending')
                    <x-tag tone="w">در انتظار بررسی</x-tag>
                @elseif($consultationStatus === 'scheduled')
                    <x-tag>هماهنگ‌شده</x-tag>
                @elseif($consultationStatus === 'done')
                    <x-tag tone="d">انجام‌شده</x-tag>
                @else
                    <x-tag tone="r">بسته‌شده</x-tag>
                @endif
            </a>
        @empty
            <div class="empty">هنوز درخواستی ثبت نکرده‌اید.</div>
        @endforelse

        @if($consultations->hasPages())
            <div class="frm">{{ $consultations->links() }}</div>
        @endif
    </x-card>
@endsection
