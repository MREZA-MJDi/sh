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
use App\Services\Learning\LocalVideoProcessor;
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
        $processor = app(LocalVideoProcessor::class);
        $asset = MediaAsset::query()->find($this->mediaAssetId);

        if (! $asset || $asset->provider !== 'local' || $asset->status !== 'processing') {
            return;
        }

        $disk = Storage::disk($asset->source_disk ?: 'local');
        if (blank($asset->source_path) || ! $disk->exists($asset->source_path)) {
            throw new RuntimeException('فایل مبدأ در فضای ذخیره‌سازی پیدا نشد.');
        }

        $outputDisk = Storage::disk('local');
        $processingRelative = 'private/media/'.Str::uuid().'.processing.mp4';
        $outputRelative = str_replace('.processing.mp4', '.mp4', $processingRelative);
        $outputDisk->makeDirectory(dirname($outputRelative));
        $processingPath = $outputDisk->path($processingRelative);
        $outputPath = $outputDisk->path($outputRelative);
        $published = false;

        try {
            $info = $processor->prepare($disk->path($asset->source_path), $processingPath, 1080, 24, 160);
            $videoStream = $info['video'];
            $duration = max(1, (int) round($info['duration']));

            if (! rename($processingPath, $outputPath)) {
                throw new RuntimeException('انتشار اتمیک ویدیوی پردازش‌شده ناموفق بود.');
            }

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

            $published = true;

            if ($asset->source_disk === 'local' && $asset->source_path !== $outputRelative) {
                $disk->delete($asset->source_path);
            }
        } catch (Throwable $exception) {
            if (! $published) {
                $outputDisk->delete([$processingRelative, $outputRelative]);
            }
            throw $exception;
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
