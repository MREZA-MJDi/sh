<?php

use App\Jobs\Learning\ProcessTeaserVideo;
use App\Models\Course;
use App\Models\VideoUploadSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Expire sessions under a row lock before touching their files. This prevents
 * the cleanup task from deleting a chunk while a chunk request is committing.
 */
Schedule::call(function (): void {
    VideoUploadSession::query()
        ->where('status', 'uploading')
        ->where('expires_at', '<=', now())
        ->orderBy('id')
        ->chunkById(100, function ($sessions): void {
            foreach ($sessions as $candidate) {
                $expired = DB::transaction(function () use ($candidate): ?VideoUploadSession {
                    $session = VideoUploadSession::query()
                        ->whereKey($candidate->id)
                        ->where('status', 'uploading')
                        ->where('expires_at', '<=', now())
                        ->lockForUpdate()
                        ->first();

                    if (! $session) {
                        return null;
                    }

                    $session->update(['status' => 'expired']);

                    return $session;
                });

                if (! $expired) {
                    continue;
                }

                Storage::disk('local')->deleteDirectory('chunks/'.$expired->upload_id);

                if ($expired->target !== 'teaser') {
                    continue;
                }

                $hasActiveUpload = VideoUploadSession::query()
                    ->where('target', 'teaser')
                    ->where('target_id', $expired->target_id)
                    ->where('status', 'uploading')
                    ->where('expires_at', '>', now())
                    ->exists();

                if (! $hasActiveUpload) {
                    Course::query()
                        ->whereKey($expired->target_id)
                        ->where('teaser_status', 'uploading')
                        ->update([
                            'teaser_status' => 'failed',
                            'teaser_error' => 'مهلت آپلود به پایان رسید؛ فایل را دوباره انتخاب و بارگذاری کنید.',
                        ]);
                }
            }
        });
})->everyTenMinutes()->name('prune-expired-video-upload-sessions')->withoutOverlapping();

/*
 * Recover jobs that were committed as processing but could not be enqueued
 * after a transient queue/database failure. Unique jobs prevent duplicates.
 */
Schedule::call(function (): void {
    Course::query()
        ->where('teaser_status', 'processing')
        ->whereNotNull('teaser_original_path')
        ->orderBy('id')
        ->chunkById(50, function ($courses): void {
            foreach ($courses as $course) {
                ProcessTeaserVideo::dispatch($course->id, $course->teaser_original_path);
            }
        });
})->everyFiveMinutes()->name('recover-stalled-teaser-processing')->withoutOverlapping();
