<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Learning\ProcessTeaserVideo;
use App\Models\Course;
use App\Models\VideoUploadSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ChunkUploadController extends Controller
{
    private const EXTENSIONS = ['mp4', 'mov', 'mkv', 'webm', 'm4v'];

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'target' => ['required', 'in:teaser'],
            'target_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $course = Course::query()->findOrFail($data['target_id']);
        abort_unless($request->user()->hasPermissionTo('courses.manage'), 403);

        if ($course->teaser_status === 'processing') {
            return $this->fail('تیزر قبلی هنوز در حال پردازش است؛ پس از پایان دوباره تلاش کنید.', 409);
        }

        $session = VideoUploadSession::query()->create([
            'upload_id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'target' => 'teaser',
            'target_id' => $course->id,
            'status' => 'uploading',
            'bytes_uploaded' => 0,
            'expires_at' => now()->addHours(2),
        ]);

        return response()->json(['ok' => true, 'upload_id' => $session->upload_id]);
    }

    public function chunk(Request $request): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0', 'max:2000'],
            'chunk' => ['required', 'file', 'max:' . (((int) config('learning.chunk_mb')) + 1) * 1024],
        ]);

        $disk = Storage::disk('local');
        $path = "chunks/{$data['upload_id']}/{$data['index']}.part";
        $file = $request->file('chunk');
        $maxBytes = (int) config('learning.max_teaser_mb') * 1024 * 1024;

        $session = DB::transaction(function () use ($request, $data, $disk, $path, $file, $maxBytes) {
            $session = VideoUploadSession::query()
                ->where('upload_id', $data['upload_id'])
                ->where('user_id', $request->user()->id)
                ->where('target', 'teaser')
                ->where('status', 'uploading')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->firstOrFail();

            $oldChunkBytes = $disk->exists($path) ? $disk->size($path) : 0;
            $newBytes = $session->bytes_uploaded - $oldChunkBytes + $file->getSize();
            if ($newBytes > $maxBytes) {
                abort(422, 'حجم فایل از سقف مجاز بیشتر است.');
            }

            $stored = $file->storeAs(dirname($path), basename($path), 'local');
            if (! $stored) {
                abort(500, 'ذخیره تکه آپلود ناموفق بود.');
            }

            $session->update(['bytes_uploaded' => $newBytes]);

            return $session;
        });

        return response()->json(['ok' => true, 'bytes_uploaded' => $session->bytes_uploaded]);
    }

    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'total' => ['required', 'integer', 'min:1', 'max:2000'],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $session = VideoUploadSession::query()
            ->where('upload_id', $data['upload_id'])
            ->where('user_id', $request->user()->id)
            ->where('target', 'teaser')
            ->where('status', 'uploading')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $course = Course::query()->findOrFail($session->target_id);
        abort_unless($request->user()->hasPermissionTo('courses.manage'), 403);

        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        if (! in_array($extension, self::EXTENSIONS, true)) {
            return $this->fail('فرمت فایل مجاز نیست (mp4, mov, mkv, webm).');
        }

        $disk = Storage::disk('local');
        $chunkDirectory = "chunks/{$session->upload_id}";
        for ($index = 0; $index < $data['total']; $index++) {
            if (! $disk->exists("{$chunkDirectory}/{$index}.part")) {
                return $this->fail("تکه {$index} ناقص است؛ دوباره آپلود کن.");
            }
        }

        $destination = 'uploads/' . Str::uuid() . ".{$extension}";
        $disk->makeDirectory('uploads');
        $output = fopen($disk->path($destination), 'wb');
        if (! is_resource($output)) {
            return $this->fail('ساخت فایل نهایی آپلود ناموفق بود.', 500);
        }

        for ($index = 0; $index < $data['total']; $index++) {
            $input = fopen($disk->path("{$chunkDirectory}/{$index}.part"), 'rb');
            if (! is_resource($input)) {
                fclose($output);
                $disk->delete($destination);
                return $this->fail("خواندن تکه {$index} ناموفق بود.", 500);
            }
            stream_copy_to_stream($input, $output);
            fclose($input);
        }
        fclose($output);

        $actualBytes = filesize($disk->path($destination));
        $maxBytes = (int) config('learning.max_teaser_mb') * 1024 * 1024;
        if ($actualBytes > $maxBytes || $actualBytes !== $session->bytes_uploaded) {
            $disk->delete($destination);
            return $this->fail('اندازه فایل نهایی با تکه‌های دریافت‌شده سازگار نیست یا از سقف مجاز بیشتر است.');
        }

        $mime = function_exists('mime_content_type') ? (string) mime_content_type($disk->path($destination)) : 'application/octet-stream';
        if (! str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream') {
            $disk->delete($destination);
            return $this->fail('فایل ویدیو معتبر نیست.');
        }

        if ($course->teaser_original_path) {
            $disk->delete($course->teaser_original_path);
        }

        DB::transaction(function () use ($course, $session, $destination): void {
            $course->update(['teaser_original_path' => $destination, 'teaser_status' => 'processing', 'teaser_error' => null]);
            $session->update(['status' => 'completed', 'completed_at' => now()]);
            ProcessTeaserVideo::dispatch($course->id)->afterCommit();
        });

        $disk->deleteDirectory($chunkDirectory);

        return response()->json(['ok' => true, 'status' => 'processing']);
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
