@extends('parent.layouts.app')
@section('title', 'جلسات مشاوره')
@section('content')
<x-portal.page-head icon="calendar" tone="communication" title="جلسات و درخواست‌ها"/>
@include('parent.partials.kid-picker')
<div class="g g2">
    <section class="c"><h3>درخواست جلسه جدید</h3><p class="parent-form-intro">موضوع و زمان پیشنهادی را ثبت کنید؛ زمان نهایی پس از هماهنگی آموزشگاه اعلام می‌شود.</p>
        @if($kid)
        <form method="POST" action="{{ route('guardian.meetings.store') }}" class="parent-simple-form">
            @csrf
            <label>فرزند</label>
            <input type="hidden" name="student_id" value="{{ $kid->id }}"><strong>{{ $kid->name }}</strong>
            <label>کلاس مرتبط</label>
            <select name="classroom_id" {{ ($classrooms ?? collect())->isNotEmpty() ? 'required' : '' }}>
                @if (($classrooms ?? collect())->isEmpty())
                    <option value="">بدون کلاس فعال</option>
                @else
                    <option value="" @selected(! old('classroom_id') && $classrooms->count() > 1)>انتخاب کلاس مرتبط</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) old('classroom_id', $classrooms->count() === 1 ? $classrooms->first()->id : '') === (string) $classroom->id)>{{ $classroom->name }}</option>
                    @endforeach
                @endif
            </select>
            <label>موضوع جلسه</label>
            <input name="title" value="{{ old('title') }}" required minlength="3" maxlength="180" placeholder="موضوع درخواست">
            <label>توضیحات</label>
            <textarea name="message" rows="3" maxlength="2000" placeholder="توضیحات تکمیلی (اختیاری)">{{ old('message') }}</textarea>
            <label>زمان پیشنهادی</label>
            <input type="datetime-local" name="preferred_at" value="{{ old('preferred_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
            <button class="b" type="submit">ارسال درخواست</button>
        </form>
        @else<x-portal.empty-state icon="user" title="فرزندی به حساب شما متصل نیست." description="برای اتصال پرونده با آموزشگاه تماس بگیرید."/>@endif
    </section>
    <section class="c"><h3>جلسه‌های من</h3>
        @forelse (($meetings ?? collect()) as $meeting)
            <div class="row"><div>{{ $meeting->title }}<small>{{ $meeting->audience === 'classroom' ? 'جلسه سراسری اولیا' : $meeting->student?->name }} · {{ $meeting->classroom?->name }} · {{ ($meeting->starts_at ?? $meeting->preferred_at) ? \App\Support\JalaliDate::format($meeting->starts_at ?? $meeting->preferred_at, true) : 'زمانی ثبت نشده' }}</small>
                @if ($meeting->message)<small>{{ $meeting->message }}</small>@endif
            </div>
            <i class="bd {{ $meeting->status === 'scheduled' || $meeting->status === 'completed' ? 'gn' : (in_array($meeting->status, ['declined', 'cancelled'], true) ? 'rd' : 'am') }}">{{ ['requested' => 'در انتظار بررسی', 'proposed' => 'زمان پیشنهادی', 'scheduled' => 'هماهنگ‌شده', 'completed' => 'برگزارشده', 'declined' => 'ردشده', 'cancelled' => 'لغوشده'][$meeting->status] ?? 'در حال بررسی' }}</i></div>
        @empty
            <x-portal.empty-state icon="calendar" tone="communication" title="هنوز جلسه‌ای ثبت نشده است." description="از فرم درخواست جلسه برای هماهنگی با آموزشگاه استفاده کنید."/>
        @endforelse
        <div class="parent-pagination">{{ $meetings->links() }}</div>
    </section>
</div>
@endsection
