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
use Symfony\Component\Process\Process;
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
            $encode = new Process([
                'ffmpeg', '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $input,
                '-map', '0:v:0',
                '-map', '0:a:0?',
                '-vf', 'scale=-2:min(720\,ih)',
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac', '-b:a', '96k',
                '-movflags', '+faststart',
                '-f', 'mp4',
                $public->path($videoTemp),
            ]);
            $encode->setTimeout(3300)->mustRun();

            $tempPath = $public->path($videoTemp);
            clearstatcache(true, $tempPath);
            if (! is_file($tempPath) || filesize($tempPath) < 1024) {
                throw new RuntimeException('خروجی فشرده تیزر معتبر نیست یا حجم کافی ندارد.');
            }

            $probe = new Process([
                'ffprobe', '-v', 'error',
                '-show_entries', 'format=duration:stream=codec_type,width,height',
                '-of', 'json', $tempPath,
            ]);
            $probe->setTimeout(60)->mustRun();
            $probeData = json_decode($probe->getOutput(), true);
            $streams = $probeData['streams'] ?? [];
            $hasVideo = collect($streams)->contains(
                fn (array $stream): bool => ($stream['codec_type'] ?? null) === 'video'
                    && (int) ($stream['width'] ?? 0) > 0
                    && (int) ($stream['height'] ?? 0) > 0
            );
            $duration = (float) ($probeData['format']['duration'] ?? 0);

            if (! $hasVideo || $duration <= 0) {
                throw new RuntimeException('اعتبارسنجی خروجی تیزر با FFprobe ناموفق بود.');
            }

            if (! rename($tempPath, $public->path($video))) {
                throw new RuntimeException('انتشار فایل پردازش‌شده تیزر ناموفق بود.');
            }

            $hasPoster = false;
            foreach (['1', '0'] as $second) {
                $posterProcess = new Process([
                    'ffmpeg', '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
                    '-ss', $second, '-i', $input,
                    '-frames:v', '1', '-q:v', '4',
                    $public->path($posterTemp),
                ]);
                $posterProcess->setTimeout(120)->run();

                $posterTempPath = $public->path($posterTemp);
                if ($posterProcess->isSuccessful() && is_file($posterTempPath) && filesize($posterTempPath) > 0) {
                    if (rename($posterTempPath, $public->path($poster))) {
                        $hasPoster = true;
                        break;
                    }
                }
            }

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
