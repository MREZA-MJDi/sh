@extends('student.layouts.app')
@section('title', 'گفت‌وگو')
@section('content')
<section class="page">
<x-portal.page-head icon="message" tone="communication" :title="$message->subject" :back="route('student.messages.index')" back-label="بازگشت به پیام‌ها"/>
<div class="card"><x-portal.status :value="$message->conversationStatus()"/>
 <div class="msgs"><div class="m">{{ $message->sender?->name }}: {{ $message->body }}</div>
  @foreach ($message->replies as $reply)<div class="m {{ $reply->sender_id === auth()->id() ? 'me' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>@endforeach
 </div>
 @if (auth()->user()->hasPermissionTo('messages.send.own') && $message->acceptsReplies())
  <form class="frm" method="POST" action="{{ route('student.messages.reply', $message) }}">@csrf<textarea name="body" required maxlength="30000" placeholder="پاسخ شما"></textarea><button class="btn s" type="submit">ارسال پاسخ</button></form>
 @endif
 <a class="pill" href="{{ route('student.messages.index') }}">بازگشت به پیام‌ها</a>
</div></section>
@endsection