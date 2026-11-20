<?php

namespace App\Jobs\Learning;

use App\Models\Course;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

/** تیزر خام را به MP4 فشرده (۷۲۰p، faststart) و یک پوستر تبدیل می‌کند. نیازمند ffmpeg روی سرور. */
class ProcessTeaserVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 3600;

    public function __construct(public int $courseId)
    {
        $this->onConnection('video')->onQueue('video');
    }

    public function handle(): void
    {
        $course = Course::find($this->courseId);
        if (! $course || ! $course->teaser_original_path) {
            return;
        }

        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $input = $local->path($course->teaser_original_path);

        $dir = "courses/{$course->id}/teaser";
        $public->makeDirectory($dir);
        $name = Str::uuid()->toString();
        $video = "{$dir}/{$name}.mp4";
        $poster = "{$dir}/{$name}.jpg";

        try {
            (new Process([
                'ffmpeg', '-y', '-i', $input,
                '-vf', 'scale=-2:min(720\,ih)',
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26',
                '-c:a', 'aac', '-b:a', '96k',
                '-movflags', '+faststart',
                $public->path($video),
            ]))->setTimeout(3300)->mustRun();
        } catch (Throwable $e) {
            $public->delete($video);
            $course->update(['teaser_status' => 'failed', 'teaser_error' => mb_substr($e->getMessage(), 0, 500)]);
            return;
        }

        $hasPoster = false;
        foreach (['1', '0'] as $second) {
            $p = new Process(['ffmpeg', '-y', '-ss', $second, '-i', $input, '-frames:v', '1', '-q:v', '4', $public->path($poster)]);
            $p->setTimeout(120)->run();
            if ($p->isSuccessful() && $public->exists($poster)) {
                $hasPoster = true;
                break;
            }
        }

        foreach ([$course->teaser_path, $course->teaser_poster_path] as $old) {
            if ($old) {
                $public->delete($old);
            }
        }

        $course->update([
            'teaser_path' => $video,
            'teaser_poster_path' => $hasPoster ? $poster : null,
            'teaser_status' => 'ready',
            'teaser_error' => null,
        ]);

        $local->delete($course->teaser_original_path);
        $course->update(['teaser_original_path' => null]);
    }

    public function failed(Throwable $e): void
    {
        Course::whereKey($this->courseId)->update([
            'teaser_status' => 'failed',
            'teaser_error' => mb_substr($e->getMessage(), 0, 500),
        ]);
    }
}
