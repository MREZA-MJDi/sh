@extends('student.layouts.app')
@section('title', 'پیام به مدرس‌ها')
@section('content')
<section class="page" id="messages"><div class="chat">
 <div class="card"><div class="chead"><h2>گفت‌وگوها</h2><a class="btn s" href="{{ route('student.messages.create') }}">پیام جدید</a></div>
  @forelse ($messages as $item)
   <a class="trk" href="{{ route('student.messages.index', ['message' => $item->id]) }}"><div class="th" style="background:linear-gradient(160deg,#9bd3d0,#4b6fb3);border-radius:50%"></div><div><b>{{ $item->recipient?->name ?? $item->sender?->name }}</b><span>{{ $item->subject }}</span></div><span class="tag">{{ $item->status }}</span></a>
  @empty
   <p>گفت‌وگویی ثبت نشده است.</p>
  @endforelse
  {{ $messages->links() }}
 </div>
 <div class="card"><div class="chead"><h2>{{ $selectedMessage?->subject ?? 'گفتگو' }}</h2></div>
  @if ($selectedMessage)
   <div class="msgs"><div class="m">{{ $selectedMessage->sender?->name }}: {{ $selectedMessage->body }}</div>
    @foreach ($selectedMessage->replies as $reply)<div class="m {{ $reply->sender_id === auth()->id() ? 'me' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>@endforeach
   </div>
   @if (!in_array($selectedMessage->status, ['closed','deleted'], true))
    <form class="frm" method="POST" action="{{ route('student.messages.reply', $selectedMessage) }}">@csrf<textarea class="fld" name="body" placeholder="پیامت رو بنویس..." required></textarea><button class="btn s" type="submit">ارسال</button></form>
   @endif
  @else
   <p>برای شروع گفت‌وگو، پیام جدیدی برای مدرس خود ارسال کنید.</p>
  @endif
 </div>
</div></section>
@endsection