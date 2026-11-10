@extends('layouts.parent')

@section('title', $consultation->topic ?: 'جزئیات درخواست مشاوره')

@section('content')
    <x-crumbs :items="['درخواست‌های مشاوره' => route('guardian.consultations.index'), ($consultation->topic ?: 'جزئیات درخواست') => null]"/>

    @php
        $typeLabels = ['ticket' => 'تیکت و پیام‌رسانی', 'phone' => 'تماس تلفنی', 'in_person' => 'حضوری'];
        $consultationStatus = ['approved' => 'scheduled', 'completed' => 'done'][$consultation->status] ?? $consultation->status;
        $statusLabels = ['pending' => 'در انتظار بررسی', 'scheduled' => 'هماهنگ‌شده', 'done' => 'انجام‌شده', 'cancelled' => 'لغوشده'];
    @endphp

    <div class="grid2">
        <div class="stack">
            <x-card title="جزئیات درخواست">
                <x-slot:actions>
                    @if($consultationStatus === 'pending')
                        <x-tag tone="w">در انتظار بررسی</x-tag>
                    @elseif($consultationStatus === 'scheduled')
                        <x-tag>هماهنگ‌شده</x-tag>
                    @elseif($consultationStatus === 'done')
                        <x-tag tone="d">انجام‌شده</x-tag>
                    @else
                        <x-tag tone="r">لغوشده</x-tag>
                    @endif
                </x-slot:actions>

                <dl class="dl">
                    <dt>فرزند</dt><dd>{{ $consultation->student?->name ?? '—' }}</dd>
                    <dt>نوع جلسه</dt><dd>{{ $typeLabels[$consultation->type] ?? '—' }}</dd>
                    <dt>زمان پیشنهادی</dt><dd>{{ $consultation->desired_at ? \App\Support\PersianDate::format($consultation->desired_at, 'Y/m/d H:i') : 'ثبت نشده' }}</dd>
                    <dt>زمان قطعی</dt><dd>{{ $consultation->scheduled_at ? \App\Support\PersianDate::format($consultation->scheduled_at, 'Y/m/d H:i') : 'هنوز هماهنگ نشده' }}</dd>
                    <dt>شماره تماس</dt><dd dir="ltr">{{ $consultation->contact_phone ?: '—' }}</dd>
                </dl>
                @if($consultation->notes)
                    <p class="article" style="margin-top:14px">{{ $consultation->notes }}</p>
                @endif

                @if($consultationStatus === 'pending')
                    <form method="POST" action="{{ route('guardian.consultations.destroy', $consultation) }}" class="frm" onsubmit="return confirm('درخواست مشاوره حذف شود؟')">
                        @csrf
                        @method('DELETE')
                        <button class="btn s bad" type="submit">حذف درخواست</button>
                    </form>
                @endif
            </x-card>

            <x-card title="گفتگو با آکادمی">
                <div class="msgs">
                    @forelse($consultation->replies as $reply)
                        <div class="m {{ $reply->sender_id === auth()->id() ? 'me' : '' }}">
                            <small>{{ $reply->sender?->name ?? 'کاربر آکادمی' }} · {{ \App\Support\PersianDate::format($reply->created_at, 'Y/m/d H:i') }}</small>
                            {{ $reply->body }}
                        </div>
                    @empty
                        <div class="empty">هنوز پاسخی برای این درخواست ثبت نشده است.</div>
                    @endforelse
                </div>

                @if(! in_array($consultationStatus, ['done', 'cancelled'], true))
                    <form method="POST" action="{{ route('guardian.consultations.reply', $consultation) }}">
                        @csrf
                        <div class="frm" style="align-items:flex-start">
                            <div style="flex:1;min-width:220px">
                                <label class="sr" for="body">پیام</label>
                                <textarea class="fld" id="body" name="body" data-count="5000" placeholder="پیام تکمیلی خود را بنویسید…">{{ old('body') }}</textarea>
                                <x-field-error name="body"/>
                            </div>
                            <button class="btn" type="submit">ارسال پیام</button>
                        </div>
                    </form>
                @else
                    <div class="info w" style="margin-top:14px">این درخواست بسته شده است و امکان ارسال پیام جدید ندارد.</div>
                @endif
            </x-card>
        </div>

        <x-card title="وضعیت درخواست">
            <x-steps :items="['ثبت درخواست', 'هماهنگی زمان جلسه', 'برگزاری جلسه']" :current="match($consultation->status) { 'pending' => 0, 'scheduled' => 1, 'done' => 2, default => 0 }"/>
        </x-card>
    </div>
@endsection
