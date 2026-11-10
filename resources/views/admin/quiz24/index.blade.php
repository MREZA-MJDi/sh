@extends('layouts.panel')

@section('page-title', 'کوییز ۲۴')
@section('page-description', 'مدیریت مشخصات اتصال و آزمون‌های ارجاع‌شده به Quiz24')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title">
            <div><h3>وضعیت اتصال Quiz24</h3><p>ذخیره اطلاعات اتصال با تأیید عملی ارتباط API متفاوت است.</p></div>
            <span class="panel-badge {{ $configured ? 'panel-badge-warning' : 'panel-badge-danger' }}">{{ $configured ? 'اطلاعات ذخیره شده · تست‌نشده' : 'پیکربندی نشده' }}</span>
        </div>
        <div class="panel-status-line"><span class="panel-status-dot"></span><span>{{ $configured ? 'اطلاعات حساب و کلید موجود است؛ همگام‌سازی تا پیاده‌سازی قرارداد رسمی API غیرفعال است.' : 'اطلاعات اتصال حساب Quiz24 ثبت نشده است.' }}</span></div>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>تنظیمات سرویس</h3><p>توکن در دیتابیس رمزنگاری می‌شود و مقدار فعلی در رابط کاربری نمایش داده نمی‌شود.</p></div></div>
        <form method="POST" action="{{ route('admin.quiz24.settings') }}">
            @csrf @method('PATCH')
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>نام حساب</label><input class="panel-input" name="account_name" value="{{ old('account_name', $settings?->account_name) }}" maxlength="190"></div>
                <div class="panel-field"><label>آدرس پایه API امن</label><input class="panel-input" type="url" name="api_base_url" value="{{ old('api_base_url', $settings?->api_base_url) }}" dir="ltr" placeholder="https://api.example.com"></div>
                <div class="panel-field"><label>توکن جدید</label><input class="panel-input" type="password" name="api_token" autocomplete="new-password" placeholder="{{ $settings?->api_token ? 'برای حفظ توکن فعلی خالی بگذارید' : 'توکن API' }}"></div>
            </div>
            <label class="panel-checkbox mt-3"><input type="checkbox" name="is_enabled" value="1" @checked($settings?->is_enabled)> فعال بودن تنظیمات برای استفاده پس از تکمیل اتصال</label>
            <p class="panel-muted mt-2">در حال حاضر هیچ درخواست ساختگی برای همگام‌سازی یا ورود یکپارچه ارسال نمی‌شود.</p>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره تنظیمات</button></div>
        </form>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>آزمون‌های مرتبط</h3><p>این فهرست فقط آزمون‌هایی را نشان می‌دهد که شناسه خارجی آن‌ها ثبت شده است.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table">
                <thead><tr><th>عنوان آزمون</th><th>کلاس</th><th>شناسه Quiz24</th><th>وضعیت</th><th>نتایج ثبت‌شده</th></tr></thead>
                <tbody>
                @forelse ($exams as $exam)
                    <tr><td>{{ $exam->title }}</td><td>{{ $exam->classroom->name ?? '—' }}</td><td>{{ $exam->external_id ?: '—' }}</td><td>{{ ['draft'=>'پیش‌نویس','scheduled'=>'زمان‌بندی‌شده','published'=>'منتشرشده','closed'=>'بسته‌شده'][$exam->status] ?? $exam->status }}</td><td>{{ $exam->results()->count() }}</td></tr>
                @empty
                    <tr><td colspan="5" class="panel-empty">آزمون Quiz24 ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination">{{ $exams->links() }}</div>
    </section>
@endsection
