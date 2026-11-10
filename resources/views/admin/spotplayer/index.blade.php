@extends('admin.layouts.app')

@section('page-title', 'ویدیوهای کلاس')
@section('page-description', 'ثبت و مدیریت ویدیوهای دوره‌های متصل به SpotPlayer')

@section('content')
    @if ($teacherView)
        <div class="panel-page-head">
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

        <x-admin::drawer-form id="video-create" title="ثبت ویدئوی کلاس" :action="route('admin.spotplayer.videos.store')" submit-label="ثبت ویدیو" enctype="multipart/form-data">
            <div class="panel-field"><label>عنوان ویدیو</label><input class="panel-input" name="title" required maxlength="180"></div>
            <div class="panel-field"><label>دوره</label><select class="panel-select" name="course_id" required><option value="">انتخاب دوره</option>@foreach ($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select></div>
            <div class="panel-field"><label>کلاس</label><select class="panel-select" name="classroom_id"><option value="">بدون کلاس مشخص</option>@foreach ($classrooms as $classroom)<option value="{{ $classroom->id }}">{{ $classroom->name }}</option>@endforeach</select></div>
            <div class="panel-field"><label>شناسه ویدیو در SpotPlayer</label><input class="panel-input" name="external_id" dir="ltr" maxlength="190" placeholder="شناسه‌ای که از پنل Spot دریافت کرده‌اید"></div>
            <div class="panel-field"><label>فایل ویدیو (فقط اگر هنوز در Spot بارگذاری نشده)</label><input class="panel-input" type="file" name="file" accept="video/mp4,video/quicktime,video/webm,.mp4,.mov,.m4v,.webm,.mkv"><span class="panel-muted">حداکثر ۱ گیگابایت؛ فایل فقط در فضای خصوصی شیخان نگهداری می‌شود.</span></div>
            <div class="panel-alert">اگر ویدیو از قبل در حساب SpotPlayer شیخان قرار دارد، فقط شناسه خارجی را وارد کنید و فایل را دوباره بارگذاری نکنید.</div>
            <input type="hidden" name="status" value="waiting_contract">
            <div class="panel-alert">تا زمانی که قرارداد API سرویس تحویل نشود، فایل فقط در فضای خصوصی شیخان ذخیره می‌شود و اتصال خارجی انجام‌شده تلقی نمی‌شود.</div>
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
    @endif
@endsection
