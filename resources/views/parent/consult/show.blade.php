@extends('parent.layouts.app')
@section('title', 'جزئیات درخواست مشاوره')
@section('content')
@php $kids = $children; $kid = $selected_child; $labels = ['pending'=>'در انتظار بررسی','scheduled'=>'هماهنگ‌شده','done'=>'انجام‌شده','cancelled'=>'لغوشده']; @endphp
<h2>{{ $consultation->topic }}</h2>
@include('parent.partials.kid-picker')
<section class="c">
    <div class="row"><div>وضعیت درخواست</div><i class="bd">{{ $labels[$consultation->status] ?? $consultation->status }}</i></div>
    <div class="row"><div>فرزند<small>{{ $consultation->student?->name }}</small></div></div>
    <div class="row"><div>توضیحات<small>{{ $consultation->notes }}</small></div></div>
    @if ($consultation->scheduled_at)<div class="row"><div>زمان هماهنگ‌شده<small>{{ $consultation->scheduled_at->format('Y/m/d H:i') }}</small></div></div>@endif
    @foreach ($consultation->replies as $reply)
        <div class="row"><div>{{ $reply->sender?->name }}<small>{{ $reply->body }} · {{ $reply->created_at?->format('Y/m/d H:i') }}</small></div></div>
    @endforeach
    @if (!in_array($consultation->status, ['done', 'cancelled'], true))
        <form method="POST" action="{{ route('guardian.consultations.reply', $consultation) }}">
            @csrf
            <label>پیام جدید</label>
            <textarea name="body" required minlength="2" maxlength="5000"></textarea>
            <button class="b" type="submit">ارسال پیام</button>
        </form>
    @endif
    @if ($consultation->status === 'pending')
        <form method="POST" action="{{ route('guardian.consultations.destroy', $consultation) }}">
            @csrf @method('DELETE')
            <button class="b s" type="submit">حذف درخواست</button>
        </form>
    @endif
    <a class="pill" href="{{ route('guardian.consultations.index', ['child' => $consultation->student_id]) }}">بازگشت</a>
</section>
@endsection
