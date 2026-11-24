@extends('parent.layouts.app')
@section('title', 'مرکز پیام‌ها')
@section('content')
<x-portal.page-head icon="message" tone="communication" title="مرکز پیام‌ها">
 @if(auth()->user()->hasPermissionTo('messages.send.own') && $selected_child)<a class="b" href="{{ route('guardian.messages.create', ['child' => $selected_child->id]) }}"><x-portal.icon name="plus"/> پیام جدید</a>@endif
</x-portal.page-head>
@include('parent.partials.kid-picker')
<div class="teacher-inbox">
    <aside class="teacher-inbox-list c">
        <div class="teacher-inbox-heading"><h2>گفت‌وگوها</h2><span>{{ $messages->total() }} مورد</span></div>
        <form method="GET" action="{{ route('guardian.messages.index') }}" class="parent-inbox-filters">
            <input type="hidden" name="child" value="{{ $selected_child?->id }}">
            <label><span class="sr-only">جست‌وجوی پیام</span><input type="search" name="q" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="جست‌وجو در موضوع و متن"></label>
            <label><span class="sr-only">وضعیت گفتگو</span><select name="status"><option value="">همه وضعیت‌ها</option><option value="open" @selected(($filters['status'] ?? '') === 'open')>باز</option><option value="answered" @selected(($filters['status'] ?? '') === 'answered')>پاسخ‌داده‌شده</option><option value="closed" @selected(($filters['status'] ?? '') === 'closed')>بسته</option></select></label>
            <label><span class="sr-only">مرتب‌سازی</span><select name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>جدیدترین</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>قدیمی‌ترین</option><option value="subject" @selected(($filters['sort'] ?? '') === 'subject')>موضوع</option></select></label>
            <button class="b s" type="submit">اعمال فیلتر</button><a class="b s" href="{{ route('guardian.messages.index', ['child' => $selected_child?->id]) }}">پاک‌کردن</a>
        </form>
        @forelse ($messages as $item)
            <a class="teacher-inbox-item {{ $selectedMessage?->id === $item->id ? 'is-active' : '' }}" href="{{ route('guardian.messages.index', ['child' => $selected_child?->id, 'message' => $item->id, 'q' => $filters['q'] ?? null, 'status' => $filters['status'] ?? null, 'sort' => $filters['sort'] ?? null]) }}">
                <span class="teacher-inbox-avatar">{{ mb_substr($item->recipient?->name ?? $item->sender?->name ?? 'م', 0, 1) }}</span>
                <span class="teacher-inbox-copy"><strong>{{ $item->subject ?: 'بدون موضوع' }}</strong><small>{{ $item->sender?->name }} · {{ \App\Support\JalaliDate::format($item->created_at, true) }}</small></span>
                <x-portal.status :value="$item->conversationStatus()"/>
            </a>
        @empty
            <p class="teacher-inbox-empty">برای این فرزند گفت‌وگویی ثبت نشده است.</p>
        @endforelse
        <div class="parent-pagination">{{ $messages->links() }}</div>
    </aside>
    <section class="teacher-inbox-thread c">
        @if ($selectedMessage)
            <header class="teacher-thread-head"><div><h2>{{ $selectedMessage->subject ?: 'گفت‌وگو' }}</h2><p>{{ $selectedMessage->student?->name ?? $selected_child?->name }} · {{ \App\Support\JalaliDate::format($selectedMessage->created_at, true) }}</p></div><span class="bd {{ $selectedMessage->conversationStatus() === 'open' ? 'am' : 'gn' }}">{{ ['open' => 'در انتظار پاسخ', 'answered' => 'پاسخ‌داده‌شده', 'closed' => 'بسته', 'deleted' => 'حذف‌شده'][$selectedMessage->conversationStatus()] ?? 'در جریان' }}</span></header>
            <div class="teacher-thread-messages">
                <article class="teacher-message-bubble {{ $selectedMessage->sender_id === auth()->id() ? 'is-own' : '' }}">
                    <div class="teacher-message-meta"><strong>{{ $selectedMessage->sender?->name ?? 'فرستنده' }}</strong><time>{{ \App\Support\JalaliDate::format($selectedMessage->created_at, true) }}</time></div>
                    <p>{{ $selectedMessage->body }}</p>
                </article>
                @foreach ($selectedMessage->replies as $reply)
                    <article class="teacher-message-bubble {{ $reply->sender_id === auth()->id() ? 'is-own' : '' }}">
                        <div class="teacher-message-meta"><strong>{{ $reply->sender?->name ?? 'پاسخ‌دهنده' }}</strong><time>{{ \App\Support\JalaliDate::format($reply->created_at, true) }}</time></div>
                        <p>{{ $reply->body }}</p>
                    </article>
                @endforeach
            </div>
            @if (($selectedMessage->replies_count ?? 0) > $selectedMessage->replies->count())
                <p class="parent-thread-note">برای سبک ماندن گفتگو، فقط ۵۰ پاسخ اخیر نمایش داده شده‌اند.</p>
            @endif
            @if (auth()->user()->hasPermissionTo('messages.send.own') && $selectedMessage->acceptsReplies())
                <form class="teacher-thread-reply" method="POST" action="{{ route('guardian.messages.reply', $selectedMessage) }}">@csrf<textarea name="body" required maxlength="30000" placeholder="پیام خود را بنویسید…">{{ old('body') }}</textarea><button class="b" type="submit">ارسال پیام</button></form>
            @endif
        @else
            <div class="teacher-inbox-placeholder"><strong>یک گفت‌وگو را انتخاب کنید</strong><p>پیام‌ها و پاسخ‌ها در این بخش نمایش داده می‌شوند.</p></div>
        @endif
    </section>
</div>
@endsection