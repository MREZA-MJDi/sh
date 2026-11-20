<?php

namespace App\Jobs\Learning;

use App\Exceptions\ContractPendingException;
use App\Models\CourseLesson;
use App\Services\Learning\LearningProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** فایل آپلودشده درس را به سرویس پخش می‌فرستد؛ بعد از موفقیت فایل از VPS پاک می‌شود. */
class SendLessonVideoToProvider implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 3600;
    public array $backoff = [300, 900];

    public function __construct(public int $lessonId)
    {
        $this->onConnection('video')->onQueue('video');
    }

    public function handle(LearningProvider $provider): void
    {
        $lesson = CourseLesson::find($this->lessonId);
        if (! $lesson || ! $lesson->video_upload_path) {
            return;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($lesson->video_upload_path)) {
            $lesson->update(['video_status' => 'failed', 'video_error' => 'فایل آپلودشده روی سرور پیدا نشد.']);
            return;
        }

        $lesson->update(['video_status' => 'sending', 'video_error' => null]);

        try {
            $videoId = $provider->uploadVideo($disk->path($lesson->video_upload_path), $lesson->title);
        } catch (ContractPendingException $e) {
            // فایل روی دیسک خصوصی می‌ماند تا Adapter واقعی آماده شود.
            $lesson->update(['video_status' => 'waiting_contract', 'video_error' => $e->getMessage()]);
            return;
        }

        $disk->delete($lesson->video_upload_path);
        $lesson->update(['external_video_id' => $videoId, 'video_status' => 'ready', 'video_upload_path' => null]);
    }

    public function failed(Throwable $e): void
    {
        CourseLesson::whereKey($this->lessonId)->update([
            'video_status' => 'failed',
            'video_error' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
