<?php

namespace App\Jobs\Learning;

use App\Models\SpotPlayerVideo;
use RuntimeException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Learning\LocalVideoProcessor;
use Throwable;

/**
 * Converts uploaded lesson recordings to a browser-friendly MP4 while keeping
 * both the source and processed file on the private local disk.
 */
final class ProcessCourseRecordingVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(public readonly int $videoId)
    {
        $this->onConnection('video')->onQueue('video');
    }

    public function handle(): void
    {
        $processor = app(LocalVideoProcessor::class);
        $video = SpotPlayerVideo::query()->find($this->videoId);

        if (! $video || $video->status !== 'processing' || blank($video->file_path)) {
            return;
        }

        // Previously queued provider-bound files must never be transcoded locally.
        if (filled($video->external_id)) {
            $video->update(['status' => 'waiting_contract', 'processing_error' => null]);
            return;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($video->file_path)) {
            throw new RuntimeException('فایل مبدأ در فضای خصوصی پیدا نشد.');
        }

        $sourcePath = $disk->path($video->file_path);
        $processingRelativePath = 'private/course-videos/'.Str::uuid().'.processing.mp4';
        $outputRelativePath = str_replace('.processing.mp4', '.mp4', $processingRelativePath);
        $disk->makeDirectory(dirname($outputRelativePath));
        $processingPath = $disk->path($processingRelativePath);
        $outputPath = $disk->path($outputRelativePath);

        $published = false;
        try {
            $processor->prepare($sourcePath, $processingPath, 720, 26, 128);

            if (! rename($processingPath, $outputPath)) {
                throw new RuntimeException('انتشار اتمیک ویدیوی پردازش‌شده ناموفق بود.');
            }

            $sha256 = hash_file('sha256', $outputPath);
            if (! is_string($sha256)) {
                throw new \RuntimeException('محاسبه checksum فایل پردازش‌شده ناموفق بود.');
            }

            $sourceRelativePath = $video->file_path;
            $sourceName = pathinfo($video->file_original_name ?: 'recording', PATHINFO_FILENAME);

            $video->update([
                'file_path' => $outputRelativePath,
                'file_original_name' => $sourceName.'.mp4',
                'file_mime_type' => 'video/mp4',
                'file_size_bytes' => (int) filesize($outputPath),
                'file_sha256' => $sha256,
                'status' => filled($video->external_id) ? 'waiting_contract' : 'active',
                'processing_error' => null,
                'processed_at' => now(),
            ]);

            // Keep the source until the database points at the verified MP4.
            $published = true;
            $disk->delete($sourceRelativePath);
        } catch (Throwable $exception) {
            if (! $published) {
                $disk->delete([$processingRelativePath, $outputRelativePath]);
            }
            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        SpotPlayerVideo::query()->whereKey($this->videoId)->where('status', 'processing')->update([
            'status' => 'failed',
            'processing_error' => mb_substr($exception->getMessage(), 0, 1000),
        ]);
    }
}
