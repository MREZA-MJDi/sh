@extends('parent.layouts.app')
@section('title', 'گفت‌وگو')
@section('content')
<x-portal.page-head icon="message" tone="communication" :title="$message->subject ?: 'گفت‌وگو'" :back="route('guardian.messages.index', ['child' => $selected_child?->id])"/>
@include('parent.partials.kid-picker')
<section class="c">
 <div class="msgs">
  <div class="m">{{ $message->sender?->name }}: {{ $message->body }}</div>
  @foreach ($message->replies as $reply)
   <div class="m {{ $reply->sender_id === auth()->id() ? 'me2' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>
  @endforeach
 </div>
 @if (($message->replies_count ?? 0) > $message->replies->count())<p class="parent-thread-note">برای حفظ سرعت، فقط ۵۰ پاسخ اخیر نمایش داده شده‌اند.</p>@endif
 @if (auth()->user()->hasPermissionTo('messages.send.own') && $message->acceptsReplies())
  <form method="POST" action="{{ route('guardian.messages.reply', $message) }}" class="send">@csrf<textarea name="body" required maxlength="30000" placeholder="پاسخ شما">{{ old('body') }}</textarea><button class="b" type="submit">ارسال پاسخ</button></form>
 @endif
 <a class="ic" href="{{ route('guardian.messages.index', ['child' => $selected_child?->id]) }}">بازگشت به پیام‌ها</a>
</section>
@endsection