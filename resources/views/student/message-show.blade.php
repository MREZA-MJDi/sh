@extends('student.layouts.app')
@section('title', 'گفت‌وگو')
@section('content')
<section class="page"><div class="card"><h2>{{ $message->subject }}</h2>
 <div class="msgs"><div class="m">{{ $message->sender?->name }}: {{ $message->body }}</div>
  @foreach ($message->replies as $reply)<div class="m {{ $reply->sender_id === auth()->id() ? 'me' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>@endforeach
 </div>
 @if (!in_array($message->status, ['closed','deleted'], true))
  <form class="frm" method="POST" action="{{ route('student.messages.reply', $message) }}">@csrf<textarea name="body" required maxlength="30000" placeholder="پاسخ شما"></textarea><button class="btn s" type="submit">ارسال پاسخ</button></form>
 @endif
 <a class="pill" href="{{ route('student.messages.index') }}">بازگشت به پیام‌ها</a>
</div></section>
@endsection