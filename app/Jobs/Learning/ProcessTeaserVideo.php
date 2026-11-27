<?php

namespace App\Jobs\Learning;

use App\Models\Course;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use App\Services\Learning\LocalVideoProcessor;
use Throwable;

/**
 * Converts a private teaser source into a validated, public MP4.
 *
 * The source is retained until the new output is validated and atomically
 * published in the course row. Old public assets are removed only afterwards.
 */
final class ProcessTeaserVideo implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 3600;

    public int $uniqueFor = 86400;

    public function __construct(
        public int $courseId,
        public ?string $sourcePath = null,
    ) {
        $this->onConnection('video')->onQueue('video');
    }

    public function uniqueId(): string
    {
        return 'course-teaser:'.$this->courseId.':'.($this->sourcePath ?? 'current');
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('course-teaser:'.$this->courseId))
                ->releaseAfter(30)
                ->expireAfter(3900),
        ];
    }

    public function handle(): void
    {
        $processor = app(LocalVideoProcessor::class);
        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $course = Course::query()->find($this->courseId);

        if (! $course) {
            return;
        }

        $sourcePath = $this->sourcePath ?: $course->teaser_original_path;

        // A delayed job for an older upload must never overwrite a newer teaser.
        if (! $sourcePath || $course->teaser_original_path !== $sourcePath) {
            return;
        }

        if (! $local->exists($sourcePath)) {
            throw new RuntimeException('فایل مبدأ تیزر در فضای ذخیره‌سازی وجود ندارد.');
        }

        $input = $local->path($sourcePath);
        if (! is_file($input) || ! is_readable($input) || filesize($input) < 1) {
            throw new RuntimeException('فایل مبدأ تیزر خالی یا غیرقابل‌خواندن است.');
        }

        $dir = "courses/{$course->id}/teaser";
        $public->makeDirectory($dir);

        $name = (string) Str::uuid();
        $videoTemp = "{$dir}/{$name}.processing.mp4";
        $video = "{$dir}/{$name}.mp4";
        $posterTemp = "{$dir}/{$name}.processing.jpg";
        $poster = "{$dir}/{$name}.jpg";

        try {
            $tempPath = $public->path($videoTemp);
            $processor->prepare($input, $tempPath, 720, 26, 96, 3200);

            if (! rename($tempPath, $public->path($video))) {
                throw new RuntimeException('انتشار فایل پردازش‌شده تیزر ناموفق بود.');
            }

            $hasPoster = $processor->poster($public->path($video), $public->path($posterTemp))
                && rename($public->path($posterTemp), $public->path($poster));

            if (! $hasPoster) {
                $public->delete($posterTemp);
            }

            $oldVideo = null;
            $oldPoster = null;
            $published = DB::transaction(function () use (
                $sourcePath,
                $video,
                $poster,
                $hasPoster,
                &$oldVideo,
                &$oldPoster
            ): bool {
                $lockedCourse = Course::query()->whereKey($this->courseId)->lockForUpdate()->first();

                if (! $lockedCourse || $lockedCourse->teaser_original_path !== $sourcePath) {
                    return false;
                }

                $oldVideo = $lockedCourse->teaser_path;
                $oldPoster = $lockedCourse->teaser_poster_path;

                $lockedCourse->update([
                    'teaser_path' => $video,
                    'teaser_poster_path' => $hasPoster ? $poster : null,
                    'teaser_status' => 'ready',
                    'teaser_error' => null,
                    'teaser_original_path' => null,
                ]);

                return true;
            });

            if (! $published) {
                $public->delete([$video, $poster, $posterTemp, $videoTemp]);
                return;
            }

            // From this point the database references the new validated files.
            // Cleanup failures must never roll back the publication by deleting them.
            $published = true;

            foreach ([$oldVideo, $oldPoster] as $oldPath) {
                if (! $oldPath || in_array($oldPath, [$video, $poster], true)) {
                    continue;
                }

                try {
                    $public->delete($oldPath);
                } catch (Throwable $cleanupException) {
                    report($cleanupException);
                }
            }

            try {
                $local->delete($sourcePath);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }
        } catch (Throwable $exception) {
            if (! ($published ?? false)) {
                $public->delete([$videoTemp, $video, $posterTemp, $poster]);
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        // A legacy job without a source token cannot safely identify which
        // upload it belongs to; never let it mark a newer upload as failed.
        if (! $this->sourcePath) {
            return;
        }

        Course::query()
            ->whereKey($this->courseId)
            ->where('teaser_original_path', $this->sourcePath)
            ->where('teaser_status', 'processing')
            ->update([
                'teaser_status' => 'failed',
                'teaser_error' => mb_substr($exception->getMessage(), 0, 500),
            ]);
    }
}
