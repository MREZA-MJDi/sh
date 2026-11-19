@extends('parent.layouts.app')
@section('title', 'مرکز پیام‌ها')
@section('content')
<div class="teacher-page-head">
    <div><span class="teacher-eyebrow">ارتباطات خانواده</span><h1>مرکز پیام‌ها</h1><p>گفت‌وگوهای مربوط به فرزند انتخاب‌شده را یکجا ببینید.</p></div>
    <a class="b" href="{{ route('guardian.messages.create', ['child' => $selected_child?->id]) }}">پیام جدید</a>
</div>
@include('parent.partials.kid-picker')
<div class="teacher-inbox">
    <aside class="teacher-inbox-list c">
        <div class="teacher-inbox-heading"><h2>گفت‌وگوها</h2><span>{{ $messages->total() }} مورد</span></div>
        @forelse ($messages as $item)
            <a class="teacher-inbox-item {{ $selectedMessage?->id === $item->id ? 'is-active' : '' }}" href="{{ route('guardian.messages.index', ['child' => $selected_child?->id, 'message' => $item->id]) }}">
                <span class="teacher-inbox-avatar">{{ mb_substr($item->recipient?->name ?? $item->sender?->name ?? 'م', 0, 1) }}</span>
                <span class="teacher-inbox-copy"><strong>{{ $item->subject ?: 'بدون موضوع' }}</strong><small>{{ $item->sender?->name }} · {{ $item->created_at?->format('Y/m/d H:i') }}</small></span>
                <i class="bd {{ $item->status === 'open' ? 'am' : 'gn' }}">{{ $item->status === 'open' ? 'باز' : 'پاسخ‌داده‌شده' }}</i>
            </a>
        @empty
            <p class="teacher-inbox-empty">برای این فرزند گفت‌وگویی ثبت نشده است.</p>
        @endforelse
        {{ $messages->links() }}
    </aside>
    <section class="teacher-inbox-thread c">
        @if ($selectedMessage)
            <header class="teacher-thread-head"><div><h2>{{ $selectedMessage->subject ?: 'گفت‌وگو' }}</h2><p>{{ $selectedMessage->student?->name ?? $selected_child?->name }} · {{ $selectedMessage->created_at?->format('Y/m/d H:i') }}</p></div><span class="bd {{ $selectedMessage->status === 'open' ? 'am' : 'gn' }}">{{ $selectedMessage->status === 'open' ? 'در انتظار پاسخ' : 'پاسخ‌داده‌شده' }}</span></header>
            <div class="teacher-thread-messages">
                <article class="teacher-message-bubble {{ $selectedMessage->sender_id === auth()->id() ? 'is-own' : '' }}">
                    <div class="teacher-message-meta"><strong>{{ $selectedMessage->sender?->name ?? 'فرستنده' }}</strong><time>{{ $selectedMessage->created_at?->format('Y/m/d H:i') }}</time></div>
                    <p>{{ $selectedMessage->body }}</p>
                </article>
                @foreach ($selectedMessage->replies as $reply)
                    <article class="teacher-message-bubble {{ $reply->sender_id === auth()->id() ? 'is-own' : '' }}">
                        <div class="teacher-message-meta"><strong>{{ $reply->sender?->name ?? 'پاسخ‌دهنده' }}</strong><time>{{ $reply->created_at?->format('Y/m/d H:i') }}</time></div>
                        <p>{{ $reply->body }}</p>
                    </article>
                @endforeach
            </div>
            @if (!in_array($selectedMessage->status, ['closed','deleted'], true))
                <form class="teacher-thread-reply" method="POST" action="{{ route('guardian.messages.reply', $selectedMessage) }}">@csrf<textarea name="body" required maxlength="30000" placeholder="پیام خود را بنویسید…"></textarea><button class="b" type="submit">ارسال پیام</button></form>
            @endif
        @else
            <div class="teacher-inbox-placeholder"><strong>یک گفت‌وگو را انتخاب کنید</strong><p>پیام‌ها و پاسخ‌ها در این بخش نمایش داده می‌شوند.</p></div>
        @endif
    </section>
</div>
@endsection