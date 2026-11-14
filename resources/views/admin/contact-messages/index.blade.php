@extends('layouts.panel')

@section('page-title', 'پیام‌های تماس با ما')
@section('page-description', 'پیام‌هایی که از فرم عمومی سایت ارسال شده‌اند')

@section('content')
    <section class="panel-card">
        <div class="panel-card-title"><div><h3>پیام‌های دریافتی</h3><p>آخرین پیام‌های ثبت‌شده از صفحه تماس با ما</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table">
                <thead><tr><th>فرستنده</th><th>راه ارتباطی</th><th>موضوع</th><th>پیام</th><th>تاریخ</th></tr></thead>
                <tbody>
                @forelse($messages as $message)
                    <tr>
                        <td>{{ $message->name }}</td>
                        <td dir="ltr">{{ $message->contact }}</td>
                        <td>{{ $message->topic }}</td>
                        <td style="white-space:normal;min-width:260px">{{ $message->body }}</td>
                        <td>{{ $message->created_at->format('Y/m/d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="panel-empty">هنوز پیامی ثبت نشده است.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination">{{ $messages->links() }}</div>
    </section>
@endsection
