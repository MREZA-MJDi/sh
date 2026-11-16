@extends('admin.layouts.app')

@section('page-title', 'ویدیوهای کلاس')
@section('page-description', 'ثبت و مدیریت ویدیوهای دوره‌های متصل به SpotPlayer')

@section('content')
    @php
        $operationStatusLabels = ['waiting_contract' => 'در انتظار قرارداد سرویس', 'queued' => 'در صف اجرا', 'running' => 'در حال اجرا', 'succeeded' => 'با موفقیت انجام شد', 'failed' => 'نیازمند بررسی', 'cancelled' => 'لغوشده'];
        $operationStatusTones = ['waiting_contract' => 'warning', 'queued' => 'info', 'running' => 'info', 'succeeded' => 'success', 'failed' => 'danger', 'cancelled' => 'warning'];
        $operationActionLabels = ['sync_course_catalog' => 'دریافت فهرست دوره‌ها', 'verify_course_mapping' => 'بررسی اتصال دوره', 'create_course' => 'ساخت دوره خارجی', 'issue_license' => 'صدور لایسنس', 'revoke_license' => 'ابطال لایسنس'];
    @endphp
    @if ($teacherView)
        <div class="panel-page-head" data-tone="learning">
            <div><h2>ویدیوهای کلاس</h2><p>ویدیو فقط برای دوره‌ها و کلاس‌های تخصیص‌یافته به شما ثبت می‌شود.</p></div>
            <button class="panel-btn" type="button" data-admin-drawer-open="video-create" data-drawer-title="ثبت ویدئوی کلاس">＋ ثبت ویدیو</button>
        </div>

        <section class="panel-card">
            <div class="panel-card-title"><div><h3>ویدیوهای ثبت‌شده</h3><p>ویدئوی موجود در SpotPlayer را با شناسه خارجی ثبت کنید؛ اگر فایل هنوز در Spot نیست، می‌توانید آن را به‌صورت خصوصی برای انتقال آینده آماده کنید.</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table" data-searchable-table>
                    <thead><tr><th>عنوان</th><th>دوره</th><th>کلاس</th><th>فایل</th><th>وضعیت</th><th>دریافت</th></tr></thead>
                    <tbody>
                    @forelse ($videos as $video)
                        <tr>
                            <td><strong>{{ $video->title }}</strong><div class="panel-muted">#{{ $video->id }}</div></td>
                            <td>{{ $video->course->title ?? '—' }}</td>
                            <td>{{ $video->classroom->name ?? '—' }}</td>
                            <td>{{ $video->file_original_name ?: ($video->external_id ? 'ویدئوی موجود در SpotPlayer' : '—') }}<div class="panel-muted">{{ $video->file_size_bytes ? number_format($video->file_size_bytes / 1048576, 1).' MB' : '' }}</div></td>
                            <td><x-admin::badge variant="warning">منتظر وب‌سرویس</x-admin.badge></td>
                            <td>@if($video->file_path)<a href="{{ route('admin.spotplayer.videos.download', $video) }}">دریافت خصوصی</a>@else—@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="panel-empty">هنوز ویدیویی برای کلاس‌های شما ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="panel-pagination">{{ $videos->links() }}</div>
        </section>

        <div class="spotplayer-chunk-uploader" data-max="{{ config('learning.max_lesson_mb') }}">
        <x-admin::drawer-form id="video-create" title="ثبت ویدئوی کلاس" :action="route('admin.spotplayer.videos.store')" submit-label="ثبت ویدیو" enctype="multipart/form-data">
            <div class="panel-field"><label>عنوان ویدیو</label><input class="panel-input" name="title" required maxlength="180"></div>
            <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id" required><option value="">انتخاب دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
            <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس مشخص</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>شناسه ویدیو در SpotPlayer</label><input class="panel-input" name="external_id" dir="ltr" maxlength="190" placeholder="شناسه‌ای که از پنل Spot دریافت کرده‌اید"></div>
            <div class="panel-field"><label>فایل ویدیو (فقط اگر هنوز در Spot بارگذاری نشده)</label><input class="panel-input" type="file" name="file" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.m4v,.webm,.mkv"><span class="panel-muted">حداکثر {{ number_format(config('learning.max_lesson_mb')) }} مگابایت؛ آپلود تکه‌ای و قابل بازیابی در فضای خصوصی.</span></div>
            <progress class="spotplayer-upload-progress" value="0" max="100" hidden style="width:100%"></progress><small class="spotplayer-upload-status panel-muted" role="status"></small>
            <div class="panel-alert">اگر ویدیو از قبل در حساب SpotPlayer شیخان قرار دارد، فقط شناسه خارجی را وارد کنید و فایل را دوباره بارگذاری نکنید.</div>
            <input type="hidden" name="status" value="waiting_contract">
            <div class="panel-alert">تا زمان تحویل قرارداد رسمی، شناسه فقط در شیخان ثبت می‌شود و پلیر رسمی هنوز فعال نشده است.</div>
        </x-admin.drawer-form>
    @else
        <section class="panel-card mb-4">
            <div class="panel-card-title"><div><h3>وضعیت وب‌سرویس</h3><p>هسته داخلی آماده است؛ هیچ فراخوانی خارجی تا تحویل قرارداد رسمی انجام نمی‌شود.</p></div><span class="panel-badge panel-badge-warning">قرارداد API دریافت نشده</span></div>
            <div class="panel-alert panel-alert-error">{{ $health->message }} وضعیت اتصال: {{ $health->state }}</div>
            <form method="POST" action="{{ route('admin.spotplayer.sync-courses') }}">@csrf<button class="panel-btn panel-btn-secondary" type="submit">ثبت درخواست همگام‌سازی دوره‌ها</button></form>
        </section>

        <section class="panel-card mb-4">
            <div class="panel-card-title"><div><h3>تنظیمات داخلی</h3><p>ذخیره کلید API به معنی اتصال موفق نیست.</p></div></div>
            <form method="POST" action="{{ route('admin.spotplayer.settings') }}">
                @csrf @method('PATCH')
                <div class="panel-form-grid panel-form-grid-3">
                    <div class="panel-field"><label>شناسه حساب</label><input class="panel-input" name="account_id" value="{{ old('account_id', $settings?->account_id) }}" maxlength="190"></div>
                    <div class="panel-field"><label>کلید API</label><input class="panel-input" type="password" name="api_key" autocomplete="new-password" placeholder="{{ $settings?->api_key ? 'برای حفظ کلید فعلی خالی بگذارید' : 'پس از تحویل دسترسی وارد شود' }}"></div>
                    <div class="panel-field"><label>حالت</label><select class="panel-select" name="mode"><option value="test" @selected(($settings?->mode ?? 'test') === 'test')>آزمایشی</option><option value="production" @selected(($settings?->mode ?? 'test') === 'production')>عملیاتی</option></select></div>
                    <div class="panel-field"><label>حداکثر دستگاه</label><input class="panel-input" name="max_devices" type="number" min="1" max="99" value="{{ old('max_devices', $settings?->max_devices ?? 2) }}"></div>
                </div>
                <label class="panel-checkbox"><input type="checkbox" name="watermark_enabled" value="1" @checked($settings?->watermark_enabled ?? true)> واترمارک موبایل</label>
                <div class="panel-form-actions"><button class="panel-btn" type="submit">ذخیره تنظیمات</button></div>
            </form>
        </section>

        <section class="panel-card mb-4">
            <div class="panel-card-title"><div><h3>درخواست لایسنس</h3><p>لایسنس فقط پس از پاسخ معتبر سرویس فعال می‌شود.</p></div></div>
            <form method="POST" action="{{ route('admin.spotplayer.licenses.store') }}">@csrf
                <div class="panel-form-grid panel-form-grid-2">
                    <div class="panel-field"><label>دانش‌آموز</label><select class="panel-select" name="student_id" required><option value="">انتخاب دانش‌آموز</option>@foreach ($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select></div>
                    <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id" required><option value="">انتخاب دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                </div>
                <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت درخواست لایسنس</button></div>
            </form>
        </section>

        <section class="panel-card mb-4">
            <div class="panel-card-title"><div><h3>ثبت ویدئوی دوره</h3><p>اگر ویدئو از قبل در پنل SpotPlayer شیخان بارگذاری شده، فقط شناسه خارجی آن را ثبت کنید.</p></div></div>
            <form class="spotplayer-chunk-uploader" data-max="{{ config('learning.max_lesson_mb') }}" method="POST" action="{{ route('admin.spotplayer.videos.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="panel-form-grid panel-form-grid-2">
                    <div class="panel-field"><label>عنوان ویدئو</label><input class="panel-input" name="title" required maxlength="180"></div>
                    <div class="panel-field"><label>دوره SpotPlayer</label><select class="panel-select" name="course_id" required><option value="">انتخاب دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
                    <div class="panel-field"><label>کلاس (اختیاری)</label><select class="panel-select" name="classroom_id"><option value="">محتوای کل دوره</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
                    <div class="panel-field"><label>شناسه ویدئو در SpotPlayer</label><input class="panel-input" name="external_id" dir="ltr" maxlength="190" placeholder="شناسه موجود در پنل Spot"></div>
                    <div class="panel-field"><label>فایل (فقط برای انتقال آینده، در صورت نیاز)</label><input class="panel-input" type="file" name="file" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.m4v,.webm,.mkv"><span class="panel-muted">حداکثر {{ number_format(config('learning.max_lesson_mb')) }} مگابایت؛ آپلود تکه‌ای و ذخیره فقط در فضای خصوصی شیخان</span></div>
                    <progress class="spotplayer-upload-progress" value="0" max="100" hidden style="width:100%"></progress><small class="spotplayer-upload-status panel-muted" role="status"></small>
                </div>
                <input type="hidden" name="status" value="waiting_contract">
                <div class="panel-form-actions"><button class="panel-btn" type="submit">ثبت ویدئو</button></div>
            </form>
        </section>

        <section class="panel-card">
            <div class="panel-card-title"><div><h3>ویدیوهای ثبت‌شده</h3><p>فایل‌های خصوصی و درخواست‌های انتقال</p></div></div>
            <div class="panel-table-wrap">
                <table class="panel-table" data-searchable-table>
                    <thead><tr><th>عنوان</th><th>دوره / کلاس</th><th>فایل</th><th>شناسه خارجی</th><th>وضعیت</th><th>دریافت</th></tr></thead>
                    <tbody>
                    @forelse ($videos as $video)
                        <tr>
                            <td>{{ $video->title }}</td>
                            <td>{{ $video->course->title ?? $video->classroom->name ?? '—' }}</td>
                            <td>{{ $video->file_original_name ?: ($video->external_id ? 'ویدئوی موجود در SpotPlayer' : '—') }}</td>
                            <td dir="ltr">{{ $video->external_id ?: '—' }}</td>
                            <td><x-admin::badge variant="warning">منتظر وب‌سرویس</x-admin.badge></td>
                            <td>@if($video->file_path)<a href="{{ route('admin.spotplayer.videos.download', $video) }}">دریافت خصوصی</a>@else—@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="panel-empty">ویدیویی ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="panel-pagination">{{ $videos->links() }}</div>
        </section>
        <section class="panel-card mt-4">
            <div class="panel-card-title">
                <div><h3>کارتابل عملیات SpotPlayer</h3><p>درخواست‌ها تا پاسخ معتبر سرویس با وضعیت مشخص نگهداری می‌شوند؛ وضعیت «در حال اجرا» فقط هنگام اجرای واقعی Adapter نمایش داده می‌شود.</p></div>
            </div>
            <div class="panel-table-wrap">
                <table class="panel-table">
                    <thead><tr><th>عملیات</th><th>شناسه پیگیری</th><th>وضعیت</th><th>آخرین تغییر</th></tr></thead>
                    <tbody>
                    @forelse ($operations as $operation)
                        <tr>
                            <td>{{ $operationActionLabels[$operation->action] ?? 'عملیات یکپارچه‌سازی' }}</td>
                            <td dir="ltr">{{ $operation->correlation_id ?: '—' }}</td>
                            <td><span class="panel-badge panel-badge-{{ $operationStatusTones[$operation->status] ?? 'warning' }}">{{ $operationStatusLabels[$operation->status] ?? 'در حال بررسی' }}</span></td>
                            <td>{{ $operation->updated_at?->format('Y/m/d H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="panel-empty">عملیاتی ثبت نشده است.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    @endif


@once
<script>
(function () {
    var CHUNK = {{ (int) config('learning.chunk_mb') }} * 1024 * 1024;
    var TOKEN = @json(csrf_token());
    var START_URL = @json(route('admin.uploads.start'));
    var CHUNK_URL = @json(route('admin.uploads.chunk'));
    var DONE_URL = @json(route('admin.uploads.complete'));

    async function post(url, body) {
        for (var attempt = 1; attempt <= 3; attempt++) {
            try {
                var response = await fetch(url, {
                    method: 'POST',
                    body: body,
                    headers: {'X-CSRF-TOKEN': TOKEN, 'Accept': 'application/json'}
                });
                var payload = await response.json().catch(function () { return {}; });
                if (response.ok) return payload;
                var firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                var error = new Error(firstError || payload.message || ('خطا ' + response.status));
                if (response.status < 500 && response.status !== 419) error.fatal = true;
                throw error;
            } catch (error) {
                if (error.fatal || attempt === 3) throw error;
                await new Promise(function (resolve) { setTimeout(resolve, attempt * 900); });
            }
        }
        throw new Error('خطای شبکه');
    }

    function init() {
        document.querySelectorAll('.spotplayer-chunk-uploader').forEach(function (wrapper) {
            var form = wrapper.matches('form') ? wrapper : wrapper.querySelector('form');
            if (!form || form.dataset.chunkReady === '1') return;
            form.dataset.chunkReady = '1';

            form.addEventListener('submit', async function (event) {
                var fileInput = form.querySelector('input[name="file"]');
                var file = fileInput && fileInput.files ? fileInput.files[0] : null;
                if (!file) return;

                event.preventDefault();

                var maxBytes = Number(wrapper.dataset.max || 5120) * 1024 * 1024;
                var total = Math.ceil(file.size / CHUNK);
                var progress = form.querySelector('.spotplayer-upload-progress');
                var status = form.querySelector('.spotplayer-upload-status');
                var button = form.querySelector('button[type="submit"]');

                if (file.size <= 0 || file.size > maxBytes || total > 2000) {
                    status.textContent = 'حجم یا تعداد تکه‌های فایل از محدوده مجاز بیشتر است.';
                    return;
                }

                button.disabled = true;
                progress.hidden = false;
                progress.value = 0;

                try {
                    var start = new FormData();
                    start.append('target', 'spotplayer_video');
                    start.append('target_id', form.querySelector('[name="course_id"]').value);
                    start.append('total', total);
                    start.append('title', form.querySelector('[name="title"]').value);
                    var classroom = form.querySelector('[name="classroom_id"]').value;
                    var externalId = form.querySelector('[name="external_id"]').value;
                    if (classroom) start.append('classroom_id', classroom);
                    if (externalId) start.append('external_id', externalId);

                    var started = await post(START_URL, start);
                    for (var index = 0; index < total; index++) {
                        var part = new FormData();
                        part.append('upload_id', started.upload_id);
                        part.append('index', index);
                        part.append('chunk', file.slice(index * CHUNK, Math.min((index + 1) * CHUNK, file.size)), 'part');
                        await post(CHUNK_URL, part);
                        progress.value = Math.round(((index + 1) / total) * 100);
                        status.textContent = 'در حال آپلود… ' + progress.value + '٪';
                    }

                    var done = new FormData();
                    done.append('upload_id', started.upload_id);
                    done.append('total', total);
                    done.append('filename', file.name);
                    var completed = await post(DONE_URL, done);

                    status.textContent = completed.status === 'waiting_contract'
                        ? 'فایل خصوصی ثبت شد؛ انتقال به SpotPlayer پس از دریافت قرارداد رسمی انجام می‌شود.'
                        : 'تیزر برای پردازش ثبت شد.';
                    setTimeout(function () { window.location.reload(); }, 1300);
                } catch (error) {
                    status.textContent = 'آپلود کامل نشد: ' + error.message;
                    button.disabled = false;
                }
            });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endonce

@endsection
