<?php

namespace App\Jobs\Learning;

use App\Models\Course;
use App\Models\User;
use App\Models\VideoUploadSession;
use App\Services\SpotPlayer\SpotPlayerVideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Finalizes a resumable upload away from the user's HTTP request.
 * Uploaded chunks remain available until the final file and database record
 * are committed, so a worker restart can safely retry the operation.
 */
final class FinalizeChunkUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(public readonly int $uploadSessionId)
    {
        $this->onConnection('video')->onQueue('video');
    }

    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function handle(SpotPlayerVideoService $videos): void
    {
        $session = VideoUploadSession::query()->find($this->uploadSessionId);

        if (! $session || $session->status !== 'processing') {
            return;
        }

        $metadata = $session->metadata ?? [];
        $totalChunks = (int) ($metadata['total_chunks'] ?? 0);
        $chunkSize = (int) ($metadata['chunk_size_bytes'] ?? 0);
        $totalBytes = (int) ($metadata['total_bytes'] ?? 0);
        $filename = (string) ($metadata['original_filename'] ?? '');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($totalChunks < 1 || $chunkSize < 1 || $totalBytes < 1
            || ! in_array($extension, ['mp4', 'mov', 'mkv', 'webm', 'm4v'], true)) {
            throw new RuntimeException('اطلاعات نشست آپلود برای ساخت فایل نهایی معتبر نیست.');
        }

        $maxMb = (int) config($session->target === 'teaser' ? 'learning.max_teaser_mb' : 'learning.max_lesson_mb');
        if ($totalBytes > $maxMb * 1024 * 1024) {
            throw new RuntimeException('حجم فایل از سقف مجاز بیشتر است.');
        }

        $disk = Storage::disk('local');
        $chunkDirectory = 'chunks/'.$session->upload_id;
        $destination = ($session->target === 'teaser' ? 'uploads/' : 'private/course-videos/')
            .$session->upload_id.'.'.$extension;
        $temporary = $destination.'.assembling';
        $disk->makeDirectory(dirname($destination));

        // Reuse the completed file if a worker died after the atomic rename.
        if (! $disk->exists($destination) || $disk->size($destination) !== $totalBytes) {
            $disk->delete($temporary);
            $output = fopen($disk->path($temporary), 'wb');

            if (! is_resource($output)) {
                throw new RuntimeException('ساخت فایل موقت آپلود ناموفق بود.');
            }

            try {
                $written = 0;

                for ($index = 0; $index < $totalChunks; $index++) {
                    $chunkPath = $chunkDirectory.'/'.$index.'.part';
                    $expected = min($chunkSize, $totalBytes - ($index * $chunkSize));

                    if ($expected < 1 || ! $disk->exists($chunkPath) || $disk->size($chunkPath) !== $expected) {
                        throw new RuntimeException("بخش {$index} آپلود ناقص یا خراب است؛ بخش‌های سالم حفظ شده‌اند.");
                    }

                    $input = fopen($disk->path($chunkPath), 'rb');
                    if (! is_resource($input)) {
                        throw new RuntimeException("خواندن بخش {$index} آپلود ناموفق بود.");
                    }

                    try {
                        $copied = stream_copy_to_stream($input, $output);
                    } finally {
                        fclose($input);
                    }

                    if ($copied !== $expected) {
                        throw new RuntimeException("کپی بخش {$index} آپلود کامل نشد.");
                    }

                    $written += $copied;
                }

                if (! fflush($output) || $written !== $totalBytes) {
                    throw new RuntimeException('اندازه فایل نهایی با اندازه فایل ارسالی مطابقت ندارد.');
                }
            } catch (Throwable $exception) {
                fclose($output);
                $disk->delete($temporary);
                throw $exception;
            }

            fclose($output);

            if (! rename($disk->path($temporary), $disk->path($destination))) {
                $disk->delete($temporary);
                throw new RuntimeException('انتقال فایل نهایی به فضای ذخیره‌سازی ناموفق بود.');
            }
        }

        clearstatcache(true, $disk->path($destination));
        if (! is_file($disk->path($destination)) || filesize($disk->path($destination)) !== $totalBytes) {
            throw new RuntimeException('اعتبارسنجی اندازه فایل نهایی ناموفق بود.');
        }

        $mime = function_exists('mime_content_type')
            ? (string) mime_content_type($disk->path($destination))
            : 'application/octet-stream';

        if (! str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream') {
            throw new RuntimeException('محتوای فایل ارسال‌شده ویدیو نیست.');
        }

        if ($session->target === 'teaser') {
            $oldOriginalPath = null;

            DB::transaction(function () use ($session, $destination, &$oldOriginalPath): void {
                $locked = VideoUploadSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'processing') {
                    return;
                }

                $course = Course::query()->whereKey($locked->target_id)->lockForUpdate()->firstOrFail();
                $oldOriginalPath = $course->teaser_original_path;

                $course->update([
                    'teaser_original_path' => $destination,
                    'teaser_status' => 'processing',
                    'teaser_error' => null,
                ]);

                $locked->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'metadata' => [...($locked->metadata ?? []), 'final_path' => $destination],
                ]);

                ProcessTeaserVideo::dispatch($course->id, $destination)->afterCommit();
            });

            if ($oldOriginalPath && $oldOriginalPath !== $destination) {
                $disk->delete($oldOriginalPath);
            }
        } else {
            $actor = User::query()->findOrFail($session->user_id);

            DB::transaction(function () use ($session, $metadata, $actor, $destination, $filename, $mime, $totalBytes, $videos): void {
                $locked = VideoUploadSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

                if ($locked->status !== 'processing') {
                    return;
                }

                $video = $videos->stageStoredFile(
                    $metadata,
                    $actor,
                    $destination,
                    $filename,
                    $mime,
                    $totalBytes
                );

                $locked->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'metadata' => [...($locked->metadata ?? []), 'final_path' => $destination, 'result_video_id' => $video->id],
                ]);
            });
        }

        $disk->deleteDirectory($chunkDirectory);
    }

    public function failed(Throwable $exception): void
    {
        $session = VideoUploadSession::query()->find($this->uploadSessionId);

        if (! $session || $session->status !== 'processing') {
            return;
        }

        $session->update([
            'status' => 'failed',
            'metadata' => [
                ...($session->metadata ?? []),
                'finalization_error' => mb_substr($exception->getMessage(), 0, 1000),
            ],
        ]);

        if ($session->target === 'teaser') {
            Course::query()->whereKey($session->target_id)->where('teaser_status', 'uploading')
                ->update([
                    'teaser_status' => 'failed',
                    'teaser_error' => mb_substr($exception->getMessage(), 0, 1000),
                ]);
        }
    }
}
