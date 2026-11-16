@extends('admin.layouts.app')

@section('page-title', 'پیام‌های تماس با ما')
@section('page-description', 'پیگیری پیام‌های ورودی سایت تا تعیین تکلیف نهایی')

@section('content')
    @php
        $labels = ['new' => 'جدید', 'in_progress' => 'در حال پیگیری', 'resolved' => 'رسیدگی‌شده', 'spam' => 'هرزنامه'];
        $tones = ['new' => 'warning', 'in_progress' => 'info', 'resolved' => 'success', 'spam' => 'danger'];
    @endphp
    <section class="panel-grid panel-grid-4 mb-4">
        @foreach ($labels as $key => $label)
            <a class="panel-card" href="{{ route('admin.contact-messages.index', ['status' => $key]) }}" style="text-decoration:none">
                <span class="panel-muted">{{ $label }}</span>
                <strong style="display:block;font-size:24px;margin-top:8px">{{ number_format((int) ($counts[$key] ?? 0)) }}</strong>
            </a>
        @endforeach
    </section>

    <section class="panel-card">
        <div class="panel-card-title">
            <div><h3>پیام‌های دریافتی</h3><p>هر پیام تا زمان تعیین وضعیت در کارتابل باقی می‌ماند.</p></div>
            <a class="panel-btn panel-btn-secondary panel-btn-sm" href="{{ route('admin.contact-messages.index') }}">همه پیام‌ها</a>
        </div>
        <div class="panel-list">
            @forelse ($messages as $message)
                <article class="panel-list-row" style="align-items:flex-start;gap:14px">
                    <div class="panel-avatar">{{ mb_substr($message->name, 0, 1) }}</div>
                    <div class="panel-list-main" style="min-width:0;flex:1">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <strong>{{ $message->name }} · {{ $message->topic }}</strong>
                            <x-admin::badge :variant="$tones[$message->status] ?? 'warning'">{{ $labels[$message->status] ?? 'جدید' }}</x-admin::badge>
                        </div>
                        <span dir="ltr">{{ $message->contact }} · {{ $message->created_at->format('Y/m/d H:i') }}</span>
                        <p style="white-space:pre-wrap;overflow-wrap:anywhere;margin:8px 0">{{ $message->body }}</p>
                        @if ($message->handler)
                            <small class="panel-muted">آخرین پیگیری: {{ $message->handler->name }} · {{ $message->handled_at?->format('Y/m/d H:i') }}</small>
                        @endif
                        <form method="POST" action="{{ route('admin.contact-messages.status', $message) }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:10px">
                            @csrf @method('PATCH')
                            <select class="panel-select" name="status" aria-label="وضعیت پیام">
                                @foreach ($labels as $key => $label)
                                    <option value="{{ $key }}" @selected($message->status === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="panel-btn panel-btn-secondary panel-btn-sm" type="submit">ثبت وضعیت</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="panel-empty">پیامی با این وضعیت ثبت نشده است.</div>
            @endforelse
        </div>
        <div class="panel-pagination">{{ $messages->links() }}</div>
    </section>
@endsection
