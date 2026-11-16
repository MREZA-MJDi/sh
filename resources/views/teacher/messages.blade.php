@extends('teacher.layouts.app')
@section('title', 'پیام‌های اولیا')
@section('content')
<h2>پیام‌های اولیا</h2>
<div class="chat">
    <section class="c"><div class="chead"><h3>گفت‌وگوها</h3><a class="b s" href="{{ route('teacher.messages.create') }}">پیام جدید</a></div>
        @forelse ($conversations as $conversation)
            <a class="row" href="{{ route('teacher.messages.index', ['message' => $conversation->id]) }}">
                <div>{{ $conversation->subject }}<small>{{ $conversation->student?->name ?? $conversation->sender?->name }} · {{ $conversation->created_at?->format('Y/m/d H:i') }}</small></div>
                <i class="bd {{ $conversation->status === 'open' ? 'am' : 'gn' }}">{{ $conversation->status }}</i>
            </a>
        @empty
            <p>گفت‌وگویی نیست.</p>
        @endforelse
        {{ $conversations->links() }}
    </section>

    <section class="c"><h3>{{ $selectedMessage?->subject ?? 'گفت‌وگو' }}</h3>
        @if ($selectedMessage)
            <div class="msgs">
                <div class="m">{{ $selectedMessage->sender?->name }}: {{ $selectedMessage->body }}</div>
                @foreach ($selectedMessage->replies as $reply)
                    <div class="m {{ $reply->sender_id === auth()->id() ? 'me2' : '' }}">{{ $reply->sender?->name }}: {{ $reply->body }}</div>
                @endforeach
            </div>
            @if (!in_array($selectedMessage->status, ['closed', 'deleted'], true))
                <form class="send" method="POST" action="{{ route('teacher.messages.reply', $selectedMessage) }}">
                    @csrf
                    <textarea name="body" required maxlength="30000" placeholder="پاسخ خود را بنویسید…"></textarea>
                    <button class="b" type="submit">ارسال پاسخ</button>
                </form>
            @endif
        @else
            <p>برای مشاهده جزئیات، یک گفت‌وگو را انتخاب کنید.</p>
        @endif
    </section>
</div>
@endsection
