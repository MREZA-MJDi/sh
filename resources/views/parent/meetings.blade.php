@extends('parent.layouts.app')
@section('title', 'جلسات مشاوره')
@section('content')
<h2>جلسات مشاوره</h2>
@include('parent.partials.kid-picker')
<div class="g g2">
    <section class="c"><h3>درخواست جلسه جدید</h3>
        <form method="POST" action="{{ route('guardian.meetings.store') }}">
            @csrf
            <label>فرزند</label>
            <select name="student_id" required>
                @foreach (($kids ?? []) as $child)
                    <option value="{{ $child->id }}" @selected($child->id === ($kid?->id))>{{ $child->name }}</option>
                @endforeach
            </select>
            <label>کلاس مرتبط</label>
            <select name="classroom_id">
                <option value="">بدون انتخاب کلاس</option>
                @foreach (($classrooms ?? []) as $classroom)
                    <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                @endforeach
            </select>
            <label>موضوع جلسه</label>
            <input name="title" required minlength="3" maxlength="180" placeholder="موضوع درخواست">
            <label>توضیحات</label>
            <textarea name="message" rows="3" maxlength="2000" placeholder="توضیحات تکمیلی (اختیاری)"></textarea>
            <label>زمان پیشنهادی</label>
            <input type="datetime-local" name="preferred_at" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
            <button class="b" type="submit">ارسال درخواست</button>
        </form>
    </section>
    <section class="c"><h3>جلسه‌های من</h3>
        @forelse (($meetings ?? collect()) as $meeting)
            <div class="row"><div>{{ $meeting->title }}<small>{{ $meeting->student?->name }} · {{ $meeting->classroom?->name }} · {{ ($meeting->starts_at ?? $meeting->preferred_at)?->format('Y/m/d H:i') }}</small>
                @if ($meeting->message)<small>{{ $meeting->message }}</small>@endif
            </div>
            <i class="bd {{ $meeting->status === 'scheduled' ? 'gn' : 'am' }}">{{ $meeting->status }}</i></div>
        @empty
            <p>جلسه‌ای ثبت نشده است.</p>
        @endforelse
        {{ $meetings->links() }}
    </section>
</div>
@endsection
