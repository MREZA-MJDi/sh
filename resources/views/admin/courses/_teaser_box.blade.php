{{-- جایگزین بخش «ویدیوی تیزر» در curriculum.blade.php --}}
@php($statusLabels = ['none' => 'بدون تیزر', 'uploading' => 'در حال آپلود…', 'processing' => 'در حال پردازش…', 'ready' => 'آماده', 'failed' => 'ناموفق'])
<section class="card" style="margin-bottom:24px;padding:16px">
    <h2>ویدیوی تیزر (عمومی)</h2>
    <p>عمومی است و از SpotPlayer عبور نمی‌کند. فایل خام آپلود می‌شود، در صف به MP4 فشرده ۷۲۰p تبدیل می‌شود و پوستر ساخته می‌شود.
       حداکثر {{ config('learning.max_teaser_mb') }} مگابایت.</p>
    <p>وضعیت: <strong id="teaser-status">{{ $statusLabels[$course->teaser_status] ?? $course->teaser_status }}</strong>
        @if ($course->teaser_status === 'failed' && $course->teaser_error) <br><small style="color:#b91c1c">{{ $course->teaser_error }}</small> @endif
    </p>

    @if ($course->teaser_status === 'ready' && $course->teaser_url)
        <video controls preload="none" style="max-width:480px;width:100%"
               @if($course->teaser_poster_url) poster="{{ $course->teaser_poster_url }}" @endif>
            <source src="{{ $course->teaser_url }}" type="video/mp4">
        </video>
    @endif

    @include('admin.courses._uploader', ['target' => 'teaser', 'id' => $course->id, 'maxMb' => config('learning.max_teaser_mb')])

    @if ($course->teaser_status !== 'none')
        <form method="POST" action="{{ route('admin.courses.teaser.destroy', $course) }}" onsubmit="return confirm('تیزر حذف شود؟')">
            @csrf @method('DELETE')
            <button class="btn btn-danger" type="submit">حذف تیزر</button>
        </form>
    @endif

    @if ($course->teaser_status === 'processing')
        <script>
            (function () {
                var t = setInterval(function () {
                    fetch(@json(route('admin.courses.teaser.status', $course)), {headers: {'Accept': 'application/json'}})
                        .then(function (r) { return r.json(); })
                        .then(function (d) { if (d.status !== 'processing') { clearInterval(t); location.reload(); } })
                        .catch(function () {});
                }, 5000);
            })();
        </script>
    @endif
</section>
