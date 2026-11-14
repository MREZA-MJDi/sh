@extends('layouts.panel')

@section('page-title', 'پیام‌های تماس با ما')
@section('page-description', 'پیام‌هایی که از فرم عمومی سایت ارسال شده‌اند')

@section('content')
    <section class="panel-card">
        <div class="panel-card-title"><div><h3>پیام‌های دریافتی</h3><p>آخرین پیام‌های ثبت‌شده از صفحه تماس با ما</p></div></div>
        <div class="panel-list">
            @forelse($messages as $message)
                <article class="panel-list-row" style="align-items:flex-start">
                    <div class="panel-avatar">{{ mb_substr($message->name, 0, 1) }}</div>
                    <div class="panel-list-main" style="min-width:0">
                        <strong>{{ $message->name }} · {{ $message->topic }}</strong>
                        <span dir="ltr">{{ $message->contact }} · {{ $message->created_at->format('Y/m/d H:i') }}</span>
                        <p style="white-space:pre-wrap;overflow-wrap:anywhere;margin:8px 0 0">{{ $message->body }}</p>
                    </div>
                </article>
            @empty
                <div class="panel-empty">هنوز پیامی ثبت نشده است.</div>
            @endforelse
        </div>
        <div class="panel-pagination">{{ $messages->links() }}</div>
    </section>
@endsection
