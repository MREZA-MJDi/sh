@extends('layouts.panel')

@section('page-title', 'اسپات پلیر')
@section('page-description', 'مدیریت نگاشت دوره‌ها، درخواست لایسنس و محتوای ویدیویی')

@section('content')
    <section class="panel-card mb-4">
        <div class="panel-card-title">
            <div><h3>وضعیت وب‌سرویس</h3><p>هسته داخلی آماده است؛ هیچ فراخوانی خارجی تا تحویل قرارداد رسمی انجام نمی‌شود.</p></div>
            <span class="panel-badge panel-badge-warning">قرارداد API دریافت نشده</span>
        </div>
        <div class="panel-alert panel-alert-error">
            {{ $health->message }}
            وضعیت اتصال: {{ $health->state }}
        </div>
        <form method="POST" action="{{ route('admin.spotplayer.sync-courses') }}">
            @csrf
            <button class="panel-btn panel-btn-secondary" type="submit">ثبت درخواست همگام‌سازی فهرست دوره‌ها</button>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>تنظیمات داخلی</h3><p>کلید در دیتابیس رمزنگاری می‌شود. ذخیره کلید به معنی اتصال موفق نیست.</p></div></div>
        <form method="POST" action="{{ route('admin.spotplayer.settings') }}">
            @csrf @method('PATCH')
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>شناسه حساب (در صورت ارائه)</label><input class="panel-input" name="account_id" value="{{ old('account_id', $settings?->account_id) }}" maxlength="190"></div>
                <div class="panel-field"><label>کلید API</label><input class="panel-input" name="api_key" type="password" autocomplete="new-password" placeholder="{{ $settings?->api_key ? 'برای حفظ کلید فعلی خالی بگذارید' : 'پس از تحویل دسترسی وارد شود' }}"></div>
                <div class="panel-field"><label>حالت مورد انتظار سرویس</label><select class="panel-select" name="mode"><option value="test" @selected(($settings?->mode ?? 'test')==='test')>آزمایشی</option><option value="production" @selected(($settings?->mode ?? 'test')==='production')>عملیاتی</option></select></div>
                <div class="panel-field"><label>حداکثر دستگاه مجاز</label><input class="panel-input" name="max_devices" type="number" min="1" max="99" value="{{ old('max_devices', $settings?->max_devices ?? 2) }}"></div>
            </div>
            <div class="flex flex-wrap gap-5 mt-4">
                <label class="panel-checkbox"><input type="checkbox" name="watermark_enabled" value="1" @checked($settings?->watermark_enabled ?? true)> درخواست واترمارک شماره موبایل</label>
            </div>
            <p class="panel-muted mt-2">صدور خودکار و فعال‌سازی اتصال عمداً قفل است تا API واقعی و قرارداد امنیتی آن تحویل شود.</p>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره تنظیمات داخلی</button></div>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>نگاشت دوره‌های شیخان به SpotPlayer</h3><p>دوره در شیخان ساخته می‌شود؛ شناسه دوره خارجی پس از ساخت در پنل ارائه‌دهنده اینجا ثبت می‌شود.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table">
                <thead><tr><th>دوره شیخان</th><th>شناسه خارجی</th><th>وضعیت نگاشت</th></tr></thead>
                <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td>{{ $course->title }}</td>
                        <td dir="ltr">{{ $course->spotplayer_course_id ?: 'ثبت نشده' }}</td>
                        <td><span class="panel-badge panel-badge-warning">{{ $course->integration_status === 'ready' ? 'تأیید شده' : 'منتظر تأیید وب‌سرویس' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="panel-empty">دوره ویدیویی تعریف نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="panel-muted mt-3">شناسه خارجی را از فرم ویرایش هر دوره در بخش «دوره‌ها» ثبت کنید. این شناسه تا زمان بررسی سرویس، معتبر فرض نمی‌شود.</p>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>درخواست لایسنس دانش‌آموز</h3><p>این عملیات فقط درخواست محلی قابل پیگیری می‌سازد؛ کلید لایسنس تا پاسخ واقعی API تولید نمی‌شود.</p></div></div>
        <form method="POST" action="{{ route('admin.spotplayer.licenses.store') }}">
            @csrf
            <div class="panel-form-grid panel-form-grid-2">
                <div class="panel-field"><label>دانش‌آموز</label><select class="panel-select" name="student_id" required><option value="">انتخاب دانش‌آموز</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>دوره ویدیویی</label><select class="panel-select" name="course_id" required><option value="">انتخاب دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }} — {{ $course->spotplayer_course_id ?: 'بدون شناسه خارجی' }}</option>@endforeach</select></div>
            </div>
            <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت درخواست صدور لایسنس</button></div>
        </form>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>ویدیوهای ثبت‌شده در شیخان</h3><p>در حال حاضر فقط متادیتا و ارتباط داخلی نگهداری می‌شود؛ آپلود و انتشار خارجی منتظر API است.</p></div></div>
        <form method="POST" action="{{ route('admin.spotplayer.videos.store') }}" class="mb-4">
            @csrf
            <div class="panel-form-grid panel-form-grid-3">
                <div class="panel-field"><label>عنوان ویدیو</label><input class="panel-input" name="title" required maxlength="180"></div>
                <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id" required><option value="">انتخاب دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                <div class="panel-field"><label>کلاس مرتبط</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                <div class="panel-field"><label>شناسه ویدیو در سرویس (بعد از ساخت)</label><input class="panel-input" name="external_id" maxlength="190" dir="ltr"></div>
                <div class="panel-field"><label>لینک نمایش امن (در صورت ارائه)</label><input class="panel-input" name="embed_url" type="url" dir="ltr" placeholder="https://..."></div>
                <input type="hidden" name="status" value="waiting_contract">
            </div>
            <div class="panel-form-actions"><button class="panel-btn panel-btn-secondary" type="submit">ثبت متادیتای ویدیو</button></div>
        </form>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>عنوان ویدیو</th><th>دوره / کلاس</th><th>شناسه خارجی</th><th>وضعیت</th></tr></thead>
                <tbody>
                @forelse ($videos as $video)
                    <tr><td>{{ $video->title }}</td><td>{{ $video->course->title ?? $video->classroom->name ?? '—' }}</td><td dir="ltr">{{ $video->external_id ?: '—' }}</td><td><span class="panel-badge panel-badge-warning">منتظر وب‌سرویس</span></td></tr>
                @empty
                    <tr><td colspan="4" class="panel-empty">ویدیویی ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination">{{ $videos->links() }}</div>
    </section>

    <section class="panel-card mb-4">
        <div class="panel-card-title"><div><h3>لایسنس‌ها</h3><p>وضعیت محلی از وضعیت واقعی سرویس جداست؛ مدیر نمی‌تواند بدون پاسخ API لایسنس را فعال یا باطل کند.</p></div></div>
        <div class="panel-table-wrap">
            <table class="panel-table" data-searchable-table>
                <thead><tr><th>دانش‌آموز</th><th>دوره</th><th>شناسه خارجی</th><th>وضعیت</th><th>تعداد دستگاه</th><th>عملیات</th></tr></thead>
                <tbody>
                @forelse ($licenses as $license)
                    <tr>
                        <td>{{ $license->student->name ?? '—' }}</td>
                        <td>{{ $license->course->title ?? '—' }}</td>
                        <td dir="ltr">{{ $license->external_license_id ?: '—' }}</td>
                        <td><span class="panel-badge {{ $license->status==='active' ? 'panel-badge-success' : 'panel-badge-warning' }}">{{ ['pending'=>'در انتظار','waiting_contract'=>'منتظر وب‌سرویس','queued'=>'در صف','active'=>'فعال','failed'=>'ناموفق','revoked'=>'باطل‌شده'][$license->status] ?? $license->status }}</span><div class="panel-muted">{{ $license->error_message }}</div></td>
                        <td>{{ $license->max_devices }}</td>
                        <td>
                            @if ($license->status === 'active')
                                <form method="POST" action="{{ route('admin.spotplayer.licenses.revoke', $license) }}">
                                    @csrf
                                    <input type="hidden" name="reason" value="درخواست ابطال از پنل مدیریت">
                                    <button class="panel-btn panel-btn-danger panel-btn-sm" type="submit">درخواست ابطال</button>
                                </form>
                            @else
                                <span class="panel-muted">بدون عملیات خارجی</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="panel-empty">درخواستی برای لایسنس ثبت نشده است.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="panel-pagination">{{ $licenses->links() }}</div>
    </section>

    <section class="panel-card">
        <div class="panel-card-title"><div><h3>کارتابل عملیات SpotPlayer</h3><p>تمام درخواست‌ها با شناسه پیگیری و وضعیت مستقل ثبت می‌شوند.</p></div></div>
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
