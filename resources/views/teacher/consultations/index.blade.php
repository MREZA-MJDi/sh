@extends('teacher.layouts.app')
@section('title', 'درخواست مشاوره')
@section('content')
@php
    $statusLabels = [
        'pending' => 'در انتظار بررسی',
        'scheduled' => 'زمان‌بندی‌شده',
        'approved' => 'تأییدشده',
        'done' => 'انجام‌شده',
        'completed' => 'تکمیل‌شده',
        'cancelled' => 'لغوشده',
    ];
    $typeLabels = ['ticket' => 'پیام و تیکت', 'phone' => 'تماس تلفنی', 'in_person' => 'حضوری'];
@endphp
<div class="teacher-page-head">
    <div>
        <span class="teacher-eyebrow">هماهنگی آموزشی</span>
        <h1>درخواست‌های مشاوره</h1>
        <p>درخواست‌های ثبت‌شده توسط شما و والدین دانش‌آموزان کلاس‌های تحت مسئولیتتان.</p>
    </div>
</div>
<div class="g g2">
    <section class="c">
        <h3>ثبت درخواست جدید</h3>
        <form class="teacher-form" method="POST" action="{{ route('teacher.consultations.store') }}">
            @csrf
            <label>نوع درخواست</label>
            <select name="type" required>
                <option value="ticket" @selected(old('type') === 'ticket')>پیام و تیکت</option>
                <option value="phone" @selected(old('type') === 'phone')>تماس تلفنی</option>
                <option value="in_person" @selected(old('type') === 'in_person')>حضوری</option>
            </select>
            <label>موضوع</label>
            <input name="topic" required minlength="3" maxlength="190" value="{{ old('topic') }}">
            <label>شماره تماس</label>
            <input type="tel" inputmode="tel" dir="ltr" data-phone-input name="contact_phone" maxlength="30" value="{{ old('contact_phone', auth()->user()->phone) }}">
            <label>زمان پیشنهادی</label>
            <input type="datetime-local" name="desired_at" min="{{ now()->addMinute()->format('Y-m-d\\TH:i') }}" value="{{ old('desired_at') }}">
            <label>شرح درخواست</label>
            <textarea name="body" required minlength="5" maxlength="5000">{{ old('body') }}</textarea>
            <button class="b" type="submit">ارسال درخواست</button>
        </form>
    </section>
    <section class="c">
        <h3>درخواست‌های مرتبط</h3>
        @forelse ($consultations as $consultation)
            <div class="row">
                <div>
                    {{ $consultation->topic }}
                    <small>
                        درخواست‌کننده: {{ $consultation->requester?->name ?? 'حساب حذف‌شده' }}
                        @if ($consultation->student) · دانش‌آموز: {{ $consultation->student->name }} @endif
                    </small>
                    <small>
                        {{ $typeLabels[$consultation->type] ?? $consultation->type }}
                        @if ($consultation->desired_at) · <time data-jalali-display="{{ $consultation->desired_at->format('Y-m-d H:i') }}">{{ $consultation->desired_at->format('Y/m/d H:i') }}</time> @endif
                    </small>
                </div>
                <i class="bd {{ in_array($consultation->status, ['done', 'completed', 'scheduled', 'approved'], true) ? 'gn' : 'am' }}">
                    {{ $statusLabels[$consultation->status] ?? 'در حال بررسی' }}
                </i>
            </div>
        @empty
            <p>درخواست مشاوره‌ای برای نمایش وجود ندارد.</p>
        @endforelse
        {{ $consultations->links() }}
    </section>
</div>
@endsection
