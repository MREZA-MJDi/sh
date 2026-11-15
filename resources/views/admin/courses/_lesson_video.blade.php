{{-- داخل <details> ویرایش هر درس بگذار؛ پارامتر: $lesson --}}
@php($labels = ['none' => 'بدون فایل آپلودی', 'sending' => 'در صف / در حال ارسال به سرویس پخش', 'waiting_contract' => 'منتظر تحویل API سرویس پخش', 'ready' => 'ارسال شد', 'failed' => 'ناموفق'])
<div style="border-top:1px solid #e2e8f0;margin-top:8px;padding-top:8px">
    <strong>ویدیوی درس</strong>
    <p style="margin:4px 0">
        شناسه ویدیو: <code>{{ $lesson->external_video_id ?: '—' }}</code> ·
        وضعیت آپلود: {{ $labels[$lesson->video_status] ?? $lesson->video_status }}
        @if ($lesson->video_error) <br><small style="color:#b91c1c">{{ $lesson->video_error }}</small> @endif
    </p>
    <p style="color:#64748b;margin:4px 0">
        راه سریع‌تر: ویدیو را مستقیم در پنل SpotPlayer آپلود کن و فقط «شناسه ویدیو» را در فرم بالا بنویس.
        آپلود از اینجا فقط وقتی لازم است که فایل را از این پنل بفرستی (تا {{ config('learning.max_lesson_mb') }} مگابایت).
    </p>

    @include('admin.courses._uploader', ['target' => 'lesson', 'id' => $lesson->id, 'maxMb' => config('learning.max_lesson_mb')])

    @if ($lesson->video_upload_path && in_array($lesson->video_status, ['failed', 'waiting_contract'], true))
        <form method="POST" action="{{ route('admin.lessons.video.retry', $lesson) }}">
            @csrf <button class="btn btn-sm" type="submit">تلاش دوباره برای ارسال</button>
        </form>
    @endif
</div>
