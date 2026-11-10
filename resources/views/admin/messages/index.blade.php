@extends('layouts.panel')

@section('page-title', 'پیام‌ها')
@section('page-description', 'کارتابل پیام‌های کاربران و پاسخ‌گویی متمرکز')

@section('content')
    <section class="panel-grid panel-grid-2">
        <article class="panel-card">
            <div class="panel-card-title"><div><h3>کارتابل</h3><p>پیام‌های اصلی و وضعیت پاسخ</p></div></div>
            <div class="panel-list">
                @forelse ($messages as $message)
                    <a href="{{ route('admin.messages.index', ['message' => $message->id]) }}" class="panel-list-row no-underline {{ $selected?->id === $message->id ? 'rounded-xl bg-[var(--sh-primary-soft)] px-2' : '' }}">
                        <div class="panel-avatar">{{ mb_substr($message->sender->name ?? '؟', 0, 1) }}</div>
                        <div class="panel-list-main"><strong>{{ $message->subject }}</strong><span>{{ $message->sender->name ?? 'کاربر حذف‌شده' }} · {{ $message->created_at->diffForHumans() }}</span></div>
                        <span class="panel-badge {{ $message->status==='answered' ? 'panel-badge-success' : ($message->status==='open' ? 'panel-badge-warning' : '') }}">{{ ['open'=>'باز','read'=>'خوانده‌شده','answered'=>'پاسخ‌داده‌شده'][$message->status] ?? $message->status }}</span>
                    </a>
                @empty
                    <div class="panel-empty">پیامی ثبت نشده است.</div>
                @endforelse
            </div>
            <div class="panel-pagination">{{ $messages->links() }}</div>
        </article>

        <article class="panel-card">
            @if ($selected)
                <div class="panel-card-title">
                    <div><h3>{{ $selected->subject }}</h3><p>فرستنده: {{ $selected->sender->name ?? 'کاربر حذف‌شده' }}</p></div>
                    <span class="panel-badge">{{ $selected->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="rounded-xl bg-[var(--sh-surface-soft)] p-4 text-sm leading-8">{{ $selected->body }}</div>

                <div class="mt-4">
                    <h4 class="mb-2 text-xs font-extrabold">پاسخ‌ها</h4>
                    @forelse ($selected->replies as $reply)
                        <div class="mb-2 rounded-xl border border-[var(--sh-line)] p-3">
                            <div class="mb-1 text-xs font-bold">{{ $reply->sender->name ?? 'مدیر' }} <span class="panel-muted">· {{ $reply->created_at->format('Y-m-d H:i') }}</span></div>
                            <div class="text-sm leading-7">{{ $reply->body }}</div>
                        </div>
                    @empty
                        <p class="panel-muted">هنوز پاسخی ثبت نشده است.</p>
                    @endforelse
                </div>

                <form method="POST" action="{{ route('admin.messages.reply', $selected) }}" class="mt-4">
                    @csrf
                    <div class="panel-field"><label>پاسخ به پیام</label><textarea class="panel-textarea" name="body" required maxlength="10000" placeholder="متن پاسخ را بنویسید..."></textarea></div>
                    <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت پاسخ</button></div>
                </form>
                @if ($selected->status === 'open')
                    <form method="POST" action="{{ route('admin.messages.read', $selected) }}" class="mt-3">
                        @csrf @method('PATCH')
                        <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">علامت‌گذاری به‌عنوان خوانده‌شده</button>
                    </form>
                @endif
            @else
                <div class="panel-empty">برای مشاهده جزئیات، یک پیام را از کارتابل انتخاب کنید.</div>
            @endif
        </article>
    </section>
@endsection
