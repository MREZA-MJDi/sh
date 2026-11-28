<?php

namespace App\Jobs\Learning;

use App\Models\User;
use App\Services\Learning\ContentWatermark;
use App\Services\Learning\LocalVideoProcessor;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final class RenderViewerVideo implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 3600;
    public int $uniqueFor = 7200;

    public function __construct(public string $source, public string $output, public int $viewerId)
    {
        $this->onConnection('video')->onQueue('video');
    }

    public function uniqueId(): string
    {
        return $this->output;
    }

    public static function pathFor(string $source, User $viewer): string
    {
        $disk = Storage::disk('local');
        $key = hash('sha256', serialize([
            'viewer-video-v1', $source, $disk->size($source), $disk->lastModified($source),
            $viewer->getKey(), ContentWatermark::identity($viewer),
        ]));

        return 'viewer-videos/'.$key.'.mp4';
    }

    public static function readyPath(string $source, User $viewer): ?string
    {
        $disk = Storage::disk('local');
        $output = self::pathFor($source, $viewer);
        if ($disk->exists($output)) {
            return $disk->path($output);
        }

        abort_if(Cache::has('viewer-video-failed:'.$output), 422, 'آماده‌سازی ویدیو انجام نشد. کمی بعد دوباره تلاش کنید.');
        self::dispatch($source, $output, (int) $viewer->getKey());

        return null;
    }

    public function handle(LocalVideoProcessor $processor): void
    {
        $disk = Storage::disk('local');
        $viewer = User::query()->find($this->viewerId);
        if (! $viewer?->is_active || ! $disk->exists($this->source) || $disk->exists($this->output)
            || self::pathFor($this->source, $viewer) !== $this->output) {
            return;
        }

        $disk->makeDirectory('viewer-videos');
        $temporary = $this->output.'.'.Str::uuid().'.part';

        try {
            $processor->prepare(
                $disk->path($this->source), $disk->path($temporary), 720, 25, 96,
                watermark: ContentWatermark::identity($viewer),
            );
            if (! $disk->move($temporary, $this->output)) {
                throw new RuntimeException('Could not publish the watermarked video.');
            }
        } finally {
            $disk->delete($temporary);
        }
    }

    public function failed(): void
    {
        Cache::put('viewer-video-failed:'.$this->output, true, now()->addMinutes(2));
    }
}
