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
use Symfony\Component\Process\Process;
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
        $video = SpotPlayerVideo::query()->find($this->videoId);

        if (! $video || $video->status !== 'processing' || blank($video->file_path)) {
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

        $process = null;
        $published = false;
        try {
            $process = new Process([
                'ffmpeg',
                '-nostdin',
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
                $processingPath,
            ]);
            $process->setTimeout(3300);
            $process->mustRun();

            clearstatcache(true, $processingPath);
            if (! is_file($processingPath) || filesize($processingPath) < 1024) {
                throw new RuntimeException('FFmpeg خروجی ویدیویی معتبری تولید نکرد.');
            }

            $probe = new Process([
                'ffprobe', '-v', 'error',
                '-show_entries', 'format=duration:stream=codec_type,width,height',
                '-of', 'json', $processingPath,
            ]);
            $probe->setTimeout(60);
            $probe->mustRun();
            $probeData = json_decode($probe->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            $videoStream = collect($probeData['streams'] ?? [])->first(
                fn (array $stream): bool => ($stream['codec_type'] ?? null) === 'video'
                    && (int) ($stream['width'] ?? 0) > 0
                    && (int) ($stream['height'] ?? 0) > 0
            );
            $duration = (float) ($probeData['format']['duration'] ?? 0);

            if (! $videoStream || $duration <= 0) {
                throw new RuntimeException('اعتبارسنجی ویدیوی خروجی با FFprobe ناموفق بود.');
            }

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
            $diagnostic = trim((string) $process?->getErrorOutput());
            $error = $diagnostic !== '' ? $diagnostic : $exception->getMessage();

            throw new RuntimeException($error, 0, $exception);
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
