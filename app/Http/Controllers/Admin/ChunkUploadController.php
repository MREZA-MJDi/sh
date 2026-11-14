<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Learning\ProcessTeaserVideo;
use App\Jobs\Learning\SendLessonVideoToProvider;
use App\Models\Course;
use App\Models\CourseLesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * آپلود تکه‌تکه (chunked): مرورگر فایل را ۵ مگابایتی می‌فرستد، پس نه محدودیت PHP/Nginx
 * اذیت می‌کند و نه قطع شدن شبکه کل آپلود را خراب می‌کند (هر تکه ۳ بار تلاش می‌شود).
 */
class ChunkUploadController extends Controller
{
    private const EXTENSIONS = ['mp4', 'mov', 'mkv', 'webm', 'm4v'];

    public function chunk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0', 'max:2000'],
            'chunk' => ['required', 'file', 'max:' . ((int) config('learning.chunk_mb') + 1) * 1024],
        ]);

        $request->file('chunk')->storeAs("chunks/{$data['upload_id']}", "{$data['index']}.part", 'local');

        return response()->json(['ok' => true]);
    }

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'total' => ['required', 'integer', 'min:1', 'max:2000'],
            'filename' => ['required', 'string', 'max:255'],
            'target' => ['required', 'in:teaser,lesson'],
            'target_id' => ['required', 'integer'],
        ]);

        $ext = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        if (! in_array($ext, self::EXTENSIONS, true)) {
            return $this->fail('فرمت فایل مجاز نیست (mp4, mov, mkv, webm).');
        }

        $disk = Storage::disk('local');
        $chunkDir = "chunks/{$data['upload_id']}";

        for ($i = 0; $i < $data['total']; $i++) {
            if (! $disk->exists("{$chunkDir}/{$i}.part")) {
                return $this->fail("تکه {$i} ناقص است؛ دوباره آپلود کن.");
            }
        }

        $disk->makeDirectory('uploads');
        $dest = 'uploads/' . Str::uuid() . ".{$ext}";
        $out = fopen($disk->path($dest), 'wb');
        for ($i = 0; $i < $data['total']; $i++) {
            $in = fopen($disk->path("{$chunkDir}/{$i}.part"), 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }
        fclose($out);
        $disk->deleteDirectory($chunkDir);

        $maxMb = $data['target'] === 'teaser' ? config('learning.max_teaser_mb') : config('learning.max_lesson_mb');
        $abs = $disk->path($dest);

        if (filesize($abs) > $maxMb * 1024 * 1024) {
            $disk->delete($dest);
            return $this->fail("حجم فایل بیشتر از {$maxMb} مگابایت است.");
        }

        $mime = function_exists('mime_content_type') ? (string) mime_content_type($abs) : 'video/';
        if (! str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream') {
            $disk->delete($dest);
            return $this->fail('فایل ویدیو معتبر نیست.');
        }

        if ($data['target'] === 'teaser') {
            $course = Course::findOrFail($data['target_id']);
            if ($course->teaser_original_path) {
                $disk->delete($course->teaser_original_path);
            }
            $course->update(['teaser_original_path' => $dest, 'teaser_status' => 'processing', 'teaser_error' => null]);
            ProcessTeaserVideo::dispatch($course->id)->afterCommit();
        } else {
            $lesson = CourseLesson::findOrFail($data['target_id']);
            if ($lesson->video_upload_path) {
                $disk->delete($lesson->video_upload_path);
            }
            $lesson->update(['video_upload_path' => $dest, 'video_status' => 'sending', 'video_error' => null]);
            SendLessonVideoToProvider::dispatch($lesson->id)->afterCommit();
        }

        return response()->json(['ok' => true]);
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['message' => $message], 422);
    }
}
