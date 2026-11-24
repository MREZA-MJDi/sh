@extends('parent.layouts.app')
@section('title', 'جزئیات درخواست مشاوره')
@section('content')
@php $kids = $children; $kid = $selected_child; $labels = ['pending'=>'در انتظار بررسی','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده']; @endphp
<x-portal.page-head icon="message" tone="communication" :title="$consultation->topic" :back="route('guardian.consultations.index', ['child' => $selected_child?->id])"/>
@include('parent.partials.kid-picker')
<section class="c">
    <div class="row"><div>وضعیت درخواست</div><i class="bd">{{ $labels[$consultation->status] ?? $consultation->status }}</i></div>
    <div class="row"><div>فرزند<small>{{ $consultation->student?->name }}</small></div></div>
    <div class="row"><div>توضیحات<small>{{ $consultation->notes }}</small></div></div>
    @if ($consultation->scheduled_at)<div class="row"><div>زمان هماهنگ‌شده<small>{{ \App\Support\PersianDate::format($consultation->scheduled_at) }}</small></div></div>@endif
    @foreach ($consultation->replies as $reply)
        <div class="row"><div>{{ $reply->sender?->name }}<small>{{ $reply->body }} · {{ \App\Support\PersianDate::format($reply->created_at) }}</small></div></div>
    @endforeach
    @if (($consultation->replies_count ?? 0) > $consultation->replies->count())<p class="parent-thread-note">برای حفظ سرعت، فقط ۵۰ پاسخ اخیر نمایش داده شده‌اند.</p>@endif
    @if (auth()->user()->hasPermissionTo('consultations.create') && !in_array($consultation->status, ['done', 'cancelled'], true))
        <form method="POST" action="{{ route('guardian.consultations.reply', $consultation) }}" class="parent-simple-form">
            @csrf
            <label>پیام جدید</label>
            <textarea name="body" required minlength="2" maxlength="5000">{{ old('body') }}</textarea>
            <button class="b" type="submit">ارسال پیام</button>
        </form>
    @endif
    @if (auth()->user()->hasPermissionTo('consultations.create') && $consultation->status === 'pending')
        <form method="POST" action="{{ route('guardian.consultations.destroy', $consultation) }}">
            @csrf @method('DELETE')
            <button class="b s" type="submit">حذف درخواست</button>
        </form>
    @endif
    <a class="pill" href="{{ route('guardian.consultations.index', ['child' => $consultation->student_id]) }}">بازگشت</a>
</section>
@endsection
