@extends('parent.layouts.app')
@section('title', 'پیام به مدرس')
@section('content')
<h2>پیام به مدرس</h2>
@include('parent.partials.kid-picker')
<section class="c"><div class="chead"><h3>{{ $selectedMessage?->subject ?? 'گفت‌وگوهای فرزند' }}</h3><a class="b" href="{{ route('guardian.messages.create', ['child' => $selected_child?->id]) }}">پیام جدید</a></div>
 <div class="chips">
  @foreach ($messages as $item)
   <a class="chip" href="{{ route('guardian.messages.index', ['child' => $selected_child?->id, 'message' => $item->id]) }}">{{ $item->subject }}</a>
  @endforeach
 </div>
 @if ($selectedMessage)
  <div class="msgs"><div class="m">{{ $selectedMessage->sender?->name }}: {{ $selectedMessage->body }}</div>
   @foreach ($selectedMessage->replies as $reply)<div class="m {{ $reply->sender_id === auth()->id() ? 'me2' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>@endforeach
  </div>
  @if (!in_array($selectedMessage->status, ['closed','deleted'], true))
   <form method="POST" action="{{ route('guardian.messages.reply', $selectedMessage) }}" class="send">@csrf<textarea name="body" required maxlength="30000" placeholder="پیام خود را بنویسید…"></textarea><button class="b" type="submit">ارسال پیام</button></form>
  @endif
 @else
  <p>گفت‌وگویی برای این فرزند ثبت نشده است.</p>
 @endif
</section>
@endsection