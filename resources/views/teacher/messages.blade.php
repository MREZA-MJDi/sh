@extends('teacher.layouts.app')
@section('title', 'مرکز پیام‌ها')
@section('content')
<div class="teacher-page-head">
    <div><span class="teacher-eyebrow">ارتباطات</span><h1>مرکز پیام‌ها</h1><p>همه گفت‌وگوهای مرتبط با کلاس‌ها و خانواده‌ها در یک فضای مرتب.</p></div>
    <a class="b" href="{{ route('teacher.messages.create') }}">پیام جدید</a>
</div>
<div class="teacher-inbox">
    <aside class="teacher-inbox-list c">
        <div class="teacher-inbox-heading"><h2>گفت‌وگوها</h2><span>{{ $conversations->total() }} مورد</span></div>
        <form method="GET" action="{{ route('teacher.messages.index') }}" class="teacher-inbox-filters">
            <input type="hidden" name="message" value="">
            <label><span class="sr-only">جست‌وجوی گفت‌وگو</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="جست‌وجو در پیام‌ها"></label>
            <label><span class="sr-only">وضعیت</span><select name="status"><option value="">همه وضعیت‌ها</option><option value="open" @selected(($filters['status'] ?? '') === 'open')>باز</option><option value="answered" @selected(($filters['status'] ?? '') === 'answered')>پاسخ‌داده‌شده</option><option value="closed" @selected(($filters['status'] ?? '') === 'closed')>بسته</option></select></label>
            <button class="b s" type="submit">فیلتر</button>
        </form>
        @forelse ($conversations as $conversation)
            <a class="teacher-inbox-item {{ $selectedMessage?->id === $conversation->id ? 'is-active' : '' }}" href="{{ route('teacher.messages.index', ['message' => $conversation->id]) }}">
                <span class="teacher-inbox-avatar">{{ mb_substr($conversation->student?->name ?? $conversation->sender?->name ?? 'م', 0, 1) }}</span>
                <span class="teacher-inbox-copy"><strong>{{ $conversation->subject ?: 'بدون موضوع' }}</strong><small>{{ $conversation->student?->name ?? $conversation->sender?->name }} · {{ $conversation->created_at?->format('Y/m/d H:i') }}</small></span>
                <i class="bd {{ $conversation->status === 'open' ? 'am' : 'gn' }}">{{ $conversation->status === 'open' ? 'باز' : ($conversation->status === 'answered' ? 'پاسخ‌داده‌شده' : 'بسته') }}</i>
            </a>
        @empty
            <p class="teacher-inbox-empty">هنوز گفت‌وگویی ثبت نشده است.</p>
        @endforelse
        {{ $conversations->links() }}
    </aside>
    <section class="teacher-inbox-thread c">
        @if ($selectedMessage)
            <header class="teacher-thread-head"><div><h2>{{ $selectedMessage->subject ?: 'گفت‌وگو' }}</h2><p>{{ $selectedMessage->student?->name ?? $selectedMessage->sender?->name }} · {{ $selectedMessage->created_at?->format('Y/m/d H:i') }}</p></div><span class="bd {{ $selectedMessage->status === 'open' ? 'am' : 'gn' }}">{{ $selectedMessage->status === 'open' ? 'نیازمند پاسخ' : 'در جریان' }}</span></header>
            <div class="teacher-thread-messages">
                @if($selectedMessage->replies->count() >= 50)<p class="teacher-help">برای حفظ سرعت، آخرین ۵۰ پاسخ این گفت‌وگو نمایش داده می‌شود.</p>@endif
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
            @if (!in_array($selectedMessage->status, ['closed', 'deleted'], true) && auth()->user()->hasPermissionTo('messages.reply'))
                <form class="teacher-thread-reply" method="POST" action="{{ route('teacher.messages.reply', $selectedMessage) }}">@csrf<textarea name="body" required maxlength="30000" placeholder="پاسخ خود را بنویسید…"></textarea><button class="b" type="submit">ارسال پاسخ</button></form>
            @endif
        @else
            <div class="teacher-inbox-placeholder"><strong>یک گفت‌وگو را انتخاب کنید</strong><p>پیام‌ها و پاسخ‌ها در این بخش نمایش داده می‌شوند.</p></div>
        @endif
    </section>
</div>
@endsection