<?php

namespace App\Jobs\Learning;

use App\Models\SpotPlayerVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Converts uploaded lesson recordings to a browser-friendly MP4 while keeping
 * both the source and processed file on the private local disk.
 */
final class ProcessCourseRecordingVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    public function __construct(public readonly int $videoId)
    {
        $this->onConnection('video')->onQueue('video');
    }

    public function handle(): void
    {
        $video = SpotPlayerVideo::query()->find($this->videoId);

        if (! $video || $video->status !== 'processing' || blank($video->file_path)) {
            return;
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($video->file_path)) {
            $video->update([
                'status' => 'failed',
                'processing_error' => 'فایل مبدأ در فضای خصوصی پیدا نشد.',
            ]);

            return;
        }

        $sourcePath = $disk->path($video->file_path);
        $outputRelativePath = 'private/course-videos/'.Str::uuid().'.mp4';
        $disk->makeDirectory(dirname($outputRelativePath));
        $outputPath = $disk->path($outputRelativePath);

        try {
            $process = new Process([
                'ffmpeg',
                '-hide_banner',
                '-loglevel', 'error',
                '-y',
                '-i', $sourcePath,
                '-map', '0:v:0',
                '-map', '0:a?',
                '-sn',
                '-dn',
                '-vf', 'scale=-2:min(720\\,ih)',
                '-c:v', 'libx264',
                '-preset', 'veryfast',
                '-crf', '26',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-ac', '2',
                '-movflags', '+faststart',
                '-map_metadata', '-1',
                $outputPath,
            ]);
            $process->setTimeout(3300);
            $process->mustRun();

            clearstatcache(true, $outputPath);
            if (! is_file($outputPath) || filesize($outputPath) < 1) {
                throw new \RuntimeException('FFmpeg خروجی ویدیویی معتبری تولید نکرد.');
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
            $disk->delete($sourceRelativePath);
        } catch (Throwable $exception) {
            $disk->delete($outputRelativePath);

            $video->update([
                'status' => 'failed',
                'processing_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
        }
    }

    public function failed(Throwable $exception): void
    {
        SpotPlayerVideo::query()->whereKey($this->videoId)->update([
            'status' => 'failed',
            'processing_error' => mb_substr($exception->getMessage(), 0, 1000),
        ]);
    }
}
