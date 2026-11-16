@extends('parent.layouts.app')
@section('title', 'گفت‌وگو')
@section('content')
<h2>{{ $message->subject }}</h2>
@include('parent.partials.kid-picker')
<section class="c">
 <div class="msgs">
  <div class="m">{{ $message->sender?->name }}: {{ $message->body }}</div>
  @foreach ($message->replies as $reply)
   <div class="m {{ $reply->sender_id === auth()->id() ? 'me2' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>
  @endforeach
 </div>
 @if (!in_array($message->status, ['closed','deleted'], true))
  <form method="POST" action="{{ route('guardian.messages.reply', $message) }}" class="send">@csrf<textarea name="body" required maxlength="30000" placeholder="پاسخ شما"></textarea><button class="b" type="submit">ارسال پاسخ</button></form>
 @endif
 <a class="ic" href="{{ route('guardian.messages.index', ['child' => $selected_child?->id]) }}">بازگشت به پیام‌ها</a>
</section>
@endsection