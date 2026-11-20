<?php

namespace App\Jobs\Learning;

use App\Models\MediaAsset;
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

final class ProcessMediaAssetVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600;

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(public readonly int $mediaAssetId)
    {
        $this->onConnection('video')->onQueue('video');
    }

    public function handle(): void
    {
        $asset = MediaAsset::query()->find($this->mediaAssetId);

        if (! $asset || $asset->provider !== 'local' || $asset->status !== 'processing') {
            return;
        }

        $disk = Storage::disk($asset->source_disk ?: 'local');
        if (blank($asset->source_path) || ! $disk->exists($asset->source_path)) {
            throw new RuntimeException('فایل مبدأ در فضای ذخیره‌سازی پیدا نشد.');
        }

        $outputDisk = Storage::disk('local');
        $outputRelative = 'private/media/'.Str::uuid().'.mp4';
        $outputDisk->makeDirectory(dirname($outputRelative));
        $outputPath = $outputDisk->path($outputRelative);
        $process = null;

        try {
            $process = new Process([
                'ffmpeg', '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $disk->path($asset->source_path),
                '-map', '0:v:0', '-map', '0:a?',
                '-sn', '-dn',
                '-vf', 'scale=-2:min(1080\,ih)',
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '24',
                '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-b:a', '160k',
                '-movflags', '+faststart', '-map_metadata', '-1',
                $outputPath,
            ]);
            $process->setTimeout(3300);
            $process->mustRun();

            clearstatcache(true, $outputPath);
            if (! is_file($outputPath) || filesize($outputPath) < 1) {
                throw new \RuntimeException('FFmpeg خروجی ویدیویی معتبری تولید نکرد.');
            }

            $probe = new Process([
                'ffprobe', '-v', 'error', '-show_entries',
                'format=duration:stream=width,height', '-of', 'json', $outputPath,
            ]);
            $probe->setTimeout(30);
            $probe->mustRun();
            $info = json_decode($probe->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            $videoStream = collect($info['streams'] ?? [])->first(fn (array $stream): bool => isset($stream['width'], $stream['height']));
            $duration = isset($info['format']['duration']) ? (int) round((float) $info['format']['duration']) : null;
            $sha256 = hash_file('sha256', $outputPath);

            if (! is_string($sha256)) {
                throw new \RuntimeException('محاسبه checksum فایل پردازش‌شده ناموفق بود.');
            }

            $asset->update([
                'processed_path' => $outputRelative,
                'mime_type' => 'video/mp4',
                'size_bytes' => (int) filesize($outputPath),
                'duration_seconds' => $duration,
                'width' => isset($videoStream['width']) ? (int) $videoStream['width'] : null,
                'height' => isset($videoStream['height']) ? (int) $videoStream['height'] : null,
                'sha256' => $sha256,
                'status' => 'ready',
                'processing_error' => null,
                'processed_at' => now(),
            ]);

            if ($asset->source_disk === 'local' && $asset->source_path !== $outputRelative) {
                $disk->delete($asset->source_path);
            }
        } catch (Throwable $exception) {
            $outputDisk->delete($outputRelative);
            $diagnostic = trim((string) $process?->getErrorOutput());

            throw new RuntimeException(
                $diagnostic !== '' ? $diagnostic : $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        MediaAsset::query()->whereKey($this->mediaAssetId)->where('status', 'processing')->update([
            'status' => 'failed',
            'processing_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
