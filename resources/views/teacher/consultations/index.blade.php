@extends('teacher.layouts.app')
@section('title', 'درخواست مشاوره')
@section('content')
<h2>درخواست مشاوره</h2>
<div class="g g2">
    <section class="c">
        <h3>ثبت درخواست جدید</h3>
        <form method="POST" action="{{ route('teacher.consultations.store') }}">
            @csrf
            <label>نوع درخواست</label>
            <select name="type" required>
                <option value="ticket">پیام و تیکت</option>
                <option value="phone">تماس تلفنی</option>
                <option value="in_person">حضوری</option>
            </select>
            <label>موضوع</label>
            <input name="topic" required minlength="3" maxlength="190" value="{{ old('topic') }}">
            <label>شماره تماس</label>
            <input name="contact_phone" maxlength="30" value="{{ old('contact_phone', auth()->user()->phone) }}">
            <label>زمان پیشنهادی</label>
            <input type="datetime-local" name="desired_at" min="{{ now()->addMinute()->format('Y-m-d\\TH:i') }}" value="{{ old('desired_at') }}">
            <label>شرح درخواست</label>
            <textarea name="body" required minlength="5" maxlength="5000">{{ old('body') }}</textarea>
            <button class="b" type="submit">ارسال درخواست</button>
        </form>
    </section>
    <section class="c">
        <h3>درخواست‌های من</h3>
        @forelse ($consultations as $consultation)
            <div class="row">
                <div>{{ $consultation->topic }}<small>{{ $consultation->type }} · {{ $consultation->desired_at?->format('Y/m/d H:i') }}</small></div>
                <i class="bd {{ $consultation->status === 'done' ? 'gn' : 'am' }}">{{ $consultation->status }}</i>
            </div>
        @empty
            <p>درخواستی ثبت نشده است.</p>
        @endforelse
        {{ $consultations->links() }}
    </section>
</div>
@endsection
