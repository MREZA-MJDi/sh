@extends('layouts.panel')

@section('page-title', 'کوییز ۲۴')
@section('page-description', 'مدیریت آزمون‌های مرتبط، نگاشت دانش‌آموز و همگام‌سازی نتایج')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title">
            <div><h3>وضعیت وب‌سرویس Quiz24</h3><p>هسته داخلی آماده است اما قرارداد API و دسترسی واقعی هنوز تحویل نشده است.</p></div>
            <span class="panel-badge panel-badge-warning">قرارداد API دریافت نشده</span>
        </div>
        <div class="panel-alert panel-alert-error">{{ $health->message }}</div>
        <form method="POST" action="{{ route('admin.quiz24.sync-exams') }}">
            @csrf
            <button class="panel-btn panel-btn-secondary" type="submit">ثبت درخواست دریافت فهرست آزمون‌ها</button>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>اطلاعات اولیه اتصال</h3><p>توکن رمزنگاری می‌شود؛ سرویس تا زمان تحویل مسیرها، روش احراز هویت و قرارداد پاسخ فعال نخواهد شد.</p></div></div>
        <form method="POST" action="{{ route('admin.quiz24.settings') }}">
            @csrf @method('PATCH')
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>نام حساب / سازمان</label><input class="panel-input" name="account_name" value="{{ old('account_name', $settings?->account_name) }}" maxlength="190"></div>
                <div class="panel-field"><label>آدرس پایه API (پس از تأیید کارفرما)</label><input class="panel-input" name="api_base_url" type="url" value="{{ old('api_base_url', $settings?->api_base_url) }}" dir="ltr" placeholder="https://..."></div>
                <div class="panel-field"><label>توکن API</label><input class="panel-input" name="api_token" type="password" autocomplete="new-password" placeholder="{{ $settings?->api_token ? 'برای حفظ توکن فعلی خالی بگذارید' : 'پس از دریافت دسترسی ثبت شود' }}"></div>
            </div>
            <div class="panel-muted mt-3">مسیرها، ساختار JSON و روش احراز هویت در این نسخه حدس زده نمی‌شوند؛ باید مطابق مستند رسمی سرویس در Adapter پیاده‌سازی شوند.</div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره اطلاعات اولیه</button></div>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>آزمون‌های متصل به Quiz24</h3><p>آزمون محلی و شناسه خارجی جدا نگهداری می‌شوند؛ ثبت شناسه، همگام‌سازی را اجرا نمی‌کند.</p></div><span class="panel-badge">{{ $exams->total() }} آزمون</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان</th><th>کلاس</th><th>شناسه خارجی</th><th>نتایج داخلی</th><th>وضعیت اتصال</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($exams as $exam)
                    <tr>
                        <td>{{ $exam->title }}</td>
                        <td>{{ $exam->classroom->name ?? '—' }}</td>
                        <td dir="ltr">{{ $exam->external_id ?: 'ثبت نشده' }}</td>
                        <td>{{ $exam->results_count }}</td>
                        <td><span class="panel-badge panel-badge-warning">{{ $exam->integration_status }}</span></td>
                        <td>
                            @if ($exam->external_id)
                                <form method="POST" action="{{ route('admin.quiz24.sync-results', $exam) }}">
                                    @csrf
                                    <button class="panel-btn panel-btn-sm panel-btn-secondary" type="submit">ثبت درخواست دریافت نتایج</button>
                                </form>
                            @else
                                <span class="panel-muted">ابتدا شناسه آزمون ثبت شود</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">آزمون Quiz24 ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination">{{ $exams->links() }}</div>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>نتایج بدون پروفایل متناظر</h3><p>نتیجه تا زمان اتصال دستی به دانش‌آموز، وارد کارنامه نمی‌شود. تعارض با نتیجهٔ قبلی هم در همین فهرست نگهداری می‌شود.</p></div><span class="panel-badge panel-badge-warning">{{ number_format($unmatched->total()) }} مورد</span></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>وضعیت</th><th>آزمون</th><th>شناسه شرکت‌کننده</th><th>نام برگشتی</th><th>نمره</th><th>اتصال به دانش‌آموز</th></tr></thead>
                <tbody>
                @forelse ($unmatched as $participant)
                    <tr>
                        <td><span class="panel-badge {{ $participant->status === 'conflict' ? 'panel-badge-danger' : 'panel-badge-warning' }}">{{ $participant->status === 'conflict' ? 'تعارض با نتیجه قبلی' : 'بدون پروفایل' }}</span></td>
                        <td>{{ $participant->exam->title ?? 'آزمون حذف‌شده' }}</td>
                        <td dir="ltr">{{ $participant->external_identifier }}</td>
                        <td>{{ $participant->display_name ?: '—' }}</td>
                        <td>{{ $participant->score }} / {{ $participant->max_score }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.quiz24.unmatched.resolve', $participant) }}" class="panel-inline-form">
                                @csrf
                                <select class="panel-select" name="student_id" required>
                                    <option value="">انتخاب دانش‌آموز</option>
                                    @foreach ($students as $student)
                                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                                    @endforeach
                                </select>
                                <button class="panel-btn panel-btn-sm" type="submit">اتصال و ثبت نتیجه</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">مغایرتی برای تعیین هویت وجود ندارد.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($unmatched->hasPages())
            <div class="panel-pagination">{{ $unmatched->links() }}</div>
        @endif
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>کارتابل عملیات Quiz24</h3><p>درخواست‌های ثبت‌شده و وضعیت فعلی هرکدام</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table">
                <thead><tr><th>عملیات</th><th>موجودیت</th><th>وضعیت</th><th>شناسه پیگیری</th><th>تاریخ</th></tr></thead>
                <tbody>
                @forelse ($operations as $operation)
                    <tr>
                        <td>{{ $operation->action }}</td>
                        <td>{{ class_basename($operation->entity_type ?? '—') }} #{{ $operation->entity_id ?? '—' }}</td>
                        <td><span class="panel-badge panel-badge-warning">{{ $operation->status }}</span></td>
                        <td dir="ltr">{{ $operation->correlation_id ?: '—' }}</td>
                        <td>{{ $operation->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="panel-empty">عملیاتی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
