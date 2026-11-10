@extends('layouts.panel')

@section('page-title', 'اسپات پلیر')
@section('page-description', 'مدیریت اطلاعات ویدیوها، تنظیمات و رکوردهای لایسنس')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title">
            <div><h3>وضعیت اتصال</h3><p>وضعیت اتصال فقط پس از پیاده‌سازی و تست API رسمی قابل تأیید است.</p></div>
            <span class="panel-badge {{ $configured ? 'panel-badge-warning' : 'panel-badge-danger' }}">{{ $configured ? 'اطلاعات اتصال ذخیره شده · تست‌نشده' : 'پیکربندی نشده' }}</span>
        </div>
        <div class="panel-status-line"><span class="panel-status-dot {{ $configured ? '' : '' }}"></span><span>{{ $configured ? 'کلید و شناسه حساب ثبت شده‌اند؛ صحت اتصال هنوز بررسی نشده است.' : 'برای اتصال، شناسه حساب و کلید API را از ارائه‌دهنده دریافت کنید.' }}</span></div>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>تنظیمات اتصال</h3><p>کلید API به‌صورت رمزنگاری‌شده در دیتابیس نگهداری می‌شود و مجدداً نمایش داده نمی‌شود.</p></div></div>
        <form method="POST" action="{{ route('admin.spotplayer.settings') }}">
            @csrf @method('PATCH')
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>شناسه حساب</label><input class="panel-input" name="account_id" value="{{ old('account_id', $settings?->account_id) }}" maxlength="190"></div>
                <div class="panel-field"><label>کلید API جدید</label><input class="panel-input" name="api_key" type="password" autocomplete="new-password" placeholder="{{ $settings?->api_key ? 'برای حفظ کلید فعلی خالی بگذارید' : 'کلید API' }}"></div>
                <div class="panel-field"><label>حداکثر دستگاه هر لایسنس</label><select class="panel-select" name="max_devices">@foreach ([1,2,3,4,5] as $count)<option value="{{ $count }}" @selected(($settings?->max_devices ?? 2)===$count)>{{ $count }} دستگاه</option>@endforeach</select></div>
            </div>
            <div class="flex flex-wrap gap-5 mt-4">
                <label class="panel-checkbox"><input type="checkbox" name="watermark_enabled" value="1" @checked($settings?->watermark_enabled ?? true)> واترمارک شماره موبایل</label>
                <label class="panel-checkbox"><input type="checkbox" name="auto_license_enabled" value="1" @checked($settings?->auto_license_enabled ?? false)> فعال‌سازی تنظیم محلی صدور خودکار</label>
            </div>
            <p class="panel-muted mt-2">فعال‌کردن این گزینه به‌تنهایی لایسنس واقعی صادر نمی‌کند؛ اجرای خودکار به API تأییدشده نیاز دارد.</p>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره تنظیمات</button></div>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>ثبت اطلاعات ویدیو</h3><p>این فرم متادیتای ویدیو را ذخیره می‌کند؛ ارسال و انتشار در سرویس خارجی هنوز متصل نیست.</p></div></div>
        <form method="POST" action="{{ route('admin.spotplayer.videos.store') }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان ویدیو</label><input class="panel-input" name="title" required maxlength="180"></div>
                <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id"><option value="">بدون دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>شناسه ویدیو در SpotPlayer</label><input class="panel-input" name="external_id" maxlength="190"></div>
                <div class="panel-field"><label>لینک امن پخش</label><input class="panel-input" name="embed_url" type="url" dir="ltr" placeholder="https://..."></div>
                <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status"><option value="draft">پیش‌نویس</option><option value="processing">در حال پردازش</option><option value="published">منتشرشده</option></select></div>
            </div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت اطلاعات ویدیو</button></div>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><h3>ویدیوهای ثبت‌شده</h3></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>ویدیو</th><th>دوره / کلاس</th><th>شناسه خارجی</th><th>وضعیت</th></tr></thead>
                <tbody>
                @forelse ($videos as $video)
                    <tr><td>{{ $video->title }}</td><td>{{ $video->course->title ?? $video->classroom->name ?? '—' }}</td><td>{{ $video->external_id ?: '—' }}</td><td><span class="panel-badge">{{ ['draft'=>'پیش‌نویس','processing'=>'در حال پردازش','published'=>'منتشرشده'][$video->status] ?? $video->status }}</span></td></tr>
                @empty
                    <tr><td colspan="4" class="panel-empty">ویدیویی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination">{{ $videos->links() }}</div>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>رکوردهای لایسنس</h3><p>تغییر وضعیت اینجا دیتابیس را به‌روزرسانی می‌کند؛ ابطال یا صدور در سرویس تا اتصال API انجام نمی‌شود.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>دانش‌آموز</th><th>دوره</th><th>شناسه لایسنس</th><th>وضعیت</th><th>دستگاه‌ها</th><th>ویرایش</th></tr></thead>
                <tbody>
                @forelse ($licenses as $license)
                    <tr>
                        <td>{{ $license->student->name ?? '—' }}</td><td>{{ $license->course->title ?? '—' }}</td>
                        <td>{{ $license->external_license_id ?: 'ثبت نشده' }}</td>
                        <td><span class="panel-badge {{ $license->status==='active' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['pending'=>'در انتظار','active'=>'فعال','revoked'=>'باطل‌شده'][$license->status] ?? $license->status }}</span></td>
                        <td>{{ $license->active_devices }} / {{ $license->max_devices }}</td>
                        <td>
                            <details class="panel-details"><summary>ویرایش رکورد</summary><div class="panel-details-content">
                                <form method="POST" action="{{ route('admin.spotplayer.licenses.update', $license) }}" class="grid gap-2">
                                    @csrf @method('PATCH')
                                    <div class="panel-field"><label>شناسه خارجی</label><input class="panel-input" name="external_license_id" value="{{ $license->external_license_id }}"></div>
                                    <div class="panel-field"><label>حداکثر دستگاه</label><input class="panel-input" name="max_devices" type="number" min="1" max="5" value="{{ $license->max_devices }}"></div>
                                    <div class="panel-field"><label>وضعیت</label><select class="panel-select" name="status">@foreach (['pending'=>'در انتظار','active'=>'فعال','revoked'=>'باطل‌شده'] as $key=>$value)<option value="{{ $key }}" @selected($license->status===$key)>{{ $value }}</option>@endforeach</select></div>
                                    <button class="panel-btn panel-btn-sm" type="submit">ذخیره</button>
                                </form>
                            </div></details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">لایسنسی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination">{{ $licenses->links() }}</div>
    </section>
@endsection
