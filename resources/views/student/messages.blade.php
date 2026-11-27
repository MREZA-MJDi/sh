@extends('student.layouts.app')
@section('title', 'پیام به مدرس‌ها')
@section('content')
<section class="page" id="messages">
<x-portal.page-head icon="message" tone="communication" title="مرکز پیام‌ها">@if(auth()->user()->hasPermissionTo('messages.send.own'))<a class="btn s" href="{{ route('student.messages.create') }}"><x-portal.icon name="plus"/> پیام جدید</a>@endif</x-portal.page-head>
<div class="chat">
 <div class="card"><div class="chead"><h2>گفت‌وگوها</h2></div>
  @forelse ($messages as $item)
   <a class="trk" href="{{ route('student.messages.index', ['message' => $item->id]) }}"><div class="th" style="background:linear-gradient(160deg,#9bd3d0,#4b6fb3);border-radius:50%"></div><div><b>{{ ($item->sender_id === auth()->id() ? $item->recipient?->name : $item->sender?->name) ?? 'گفت‌وگو' }}</b><span>{{ $item->subject }}</span></div><x-portal.status :value="$item->conversationStatus()"/></a>
  @empty
   <x-portal.empty-state icon="message" tone="communication" title="گفت‌وگویی ثبت نشده است."/>
  @endforelse
  {{ $messages->links() }}
 </div>
 <div class="card"><div class="chead"><h2>{{ $selectedMessage?->subject ?? 'گفتگو' }}</h2></div>
  @if ($selectedMessage)
   <div class="msgs"><div class="m">{{ $selectedMessage->sender?->name }}: {{ $selectedMessage->body }}</div>
    @foreach ($selectedMessage->replies as $reply)<div class="m {{ $reply->sender_id === auth()->id() ? 'me' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>@endforeach
   </div>
   @if (auth()->user()->hasPermissionTo('messages.send.own') && $selectedMessage->acceptsReplies())
    <form class="frm" method="POST" action="{{ route('student.messages.reply', $selectedMessage) }}">@csrf<textarea class="fld" name="body" placeholder="پیامت رو بنویس..." required></textarea><button class="btn s" type="submit">ارسال</button></form>
   @endif
  @else
   <x-portal.empty-state icon="message" tone="communication" title="گفت‌وگویی انتخاب نشده است."/>
  @endif
 </div>
</div></section>
@endsection