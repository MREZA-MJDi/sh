<?php

use Illuminate\Foundation\Inspiring;
use App\Models\Course;
use App\Models\VideoUploadSession;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function (): void {
    VideoUploadSession::query()
        ->where('status', 'uploading')
        ->where('expires_at', '<=', now())
        ->orderBy('id')
        ->chunkById(100, function ($sessions): void {
            foreach ($sessions as $session) {
                Storage::disk('local')->deleteDirectory('chunks/'.$session->upload_id);
                $session->update(['status' => 'expired']);

                $course = Course::query()->find($session->target_id);
                if ($course && $course->teaser_status === 'uploading') {
                    $course->update([
                        'teaser_status' => 'failed',
                        'teaser_error' => 'آپلود پیش از تکمیل منقضی شد؛ فایل را دوباره ارسال کنید.',
                    ]);
                }
            }
        });
})->hourly()->name('prune-expired-video-upload-sessions')->withoutOverlapping();
