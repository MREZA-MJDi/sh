<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Learning\ProcessTeaserVideo;
use App\Models\Course;
use App\Models\SpotPlayerVideo;
use App\Models\VideoUploadSession;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\SpotPlayer\SpotPlayerVideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ChunkUploadController extends Controller
{
    private const EXTENSIONS = ['mp4', 'mov', 'mkv', 'webm', 'm4v'];

    public function start(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        $data = $request->validate([
            'target' => ['required', 'in:teaser,spotplayer_video'],
            'target_id' => ['required', 'integer', 'exists:courses,id'],
            'total' => ['required', 'integer', 'min:1', 'max:2000'],
            'total_bytes' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'title' => ['required_if:target,spotplayer_video', 'nullable', 'string', 'max:180'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'external_id' => ['nullable', 'string', 'max:190'],
        ]);

        $course = $this->authorizeTarget($request, $data['target'], (int) $data['target_id'], $data, $workspace);

        if ((int) $data['total_bytes'] > $this->maxBytes($data['target'])) {
            throw ValidationException::withMessages([
                'file' => 'حجم فایل از سقف مجاز '.number_format((int) config($data['target'] === 'teaser' ? 'learning.max_teaser_mb' : 'learning.max_lesson_mb')).' مگابایت بیشتر است.',
            ]);
        }

        if ($data['target'] === 'teaser' && $course->teaser_status === 'processing') {
            return $this->fail('تیزر قبلی هنوز در حال پردازش است؛ پس از پایان دوباره تلاش کنید.', 409);
        }

        $expired = VideoUploadSession::query()
            ->where('user_id', $request->user()->id)
            ->where('target', $data['target'])
            ->where('target_id', $course->id)
            ->where('status', 'uploading')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expired as $oldSession) {
            Storage::disk('local')->deleteDirectory('chunks/'.$oldSession->upload_id);
            $oldSession->update(['status' => 'expired']);
        }

        if ($data['target'] === 'teaser') {
            $activeUpload = VideoUploadSession::query()
                ->where('target', 'teaser')
                ->where('target_id', $course->id)
                ->where('status', 'uploading')
                ->where('expires_at', '>', now())
                ->exists();

            if ($activeUpload) {
                return $this->fail('برای این دوره یک آپلود تیزر فعال وجود دارد؛ ابتدا همان را کامل یا منقضی کنید.', 409);
            }

            $course->update(['teaser_status' => 'uploading', 'teaser_error' => null]);
        }

        $metadata = ['total_chunks' => (int) $data['total'], 'total_bytes' => (int) $data['total_bytes']];
        if ($data['target'] === 'spotplayer_video') {
            $metadata += [
                'title' => trim($data['title']),
                'course_id' => $course->id,
                'classroom_id' => filled($data['classroom_id'] ?? null) ? (int) $data['classroom_id'] : null,
                'external_id' => filled($data['external_id'] ?? null) ? trim($data['external_id']) : null,
            ];
        }

        $session = VideoUploadSession::query()->create([
            'upload_id' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'target' => $data['target'],
            'target_id' => $course->id,
            'status' => 'uploading',
            'bytes_uploaded' => 0,
            'metadata' => $metadata,
            'expires_at' => now()->addHours(2),
        ]);

        return response()->json([
            'ok' => true,
            'upload_id' => $session->upload_id,
            'chunk_size_bytes' => (int) config('learning.chunk_mb') * 1024 * 1024,
        ]);
    }

    public function chunk(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0', 'max:1999'],
            'chunk' => ['required', 'file', 'max:'.((((int) config('learning.chunk_mb')) + 1) * 1024)],
        ]);

        $session = $this->findUploadSession($data['upload_id'], $request);
        abort_unless($session->status === 'uploading' && $session->expires_at->isFuture(), 409, 'نشست آپلود منقضی یا بسته شده است؛ بارگذاری را دوباره آغاز کنید.');

        $this->authorizeTarget($request, $session->target, (int) $session->target_id, $session->metadata ?? [], $workspace);

        $totalChunks = (int) ($session->metadata['total_chunks'] ?? 0);
        abort_unless($totalChunks > 0 && $data['index'] < $totalChunks, 422, 'شماره تکه خارج از محدوده فایل است.');

        $disk = Storage::disk('local');
        $path = "chunks/{$data['upload_id']}/{$data['index']}.part";
        $file = $request->file('chunk');
        $maxBytes = $this->maxBytes($session->target);

        $session = DB::transaction(function () use ($request, $data, $disk, $path, $file, $maxBytes): VideoUploadSession {
            $locked = VideoUploadSession::query()
                ->where('upload_id', $data['upload_id'])
                ->where('user_id', $request->user()->id)
                ->where('status', 'uploading')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            abort_unless($locked, 409, 'نشست آپلود دیگر فعال نیست؛ بارگذاری را دوباره آغاز کنید.');

            $oldChunkBytes = $disk->exists($path) ? $disk->size($path) : 0;
            $newBytes = (int) $locked->bytes_uploaded - $oldChunkBytes + (int) $file->getSize();
            if ($newBytes > $maxBytes) {
                abort(422, 'حجم فایل از سقف مجاز بیشتر است.');
            }

            $stored = $file->storeAs(dirname($path), basename($path), 'local');
            if (! $stored) {
                abort(500, 'ذخیره تکه آپلود ناموفق بود.');
            }

            $locked->update(['bytes_uploaded' => $newBytes]);

            return $locked->refresh();
        });

        return response()->json(['ok' => true, 'bytes_uploaded' => $session->bytes_uploaded]);
    }

    public function complete(
        Request $request,
        ClassroomWorkspaceService $workspace,
        SpotPlayerVideoService $videos
    ): JsonResponse {
        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'total' => ['required', 'integer', 'min:1', 'max:2000'],
            'filename' => ['required', 'string', 'max:255'],
        ]);

        $existing = $this->findUploadSession($data['upload_id'], $request);

        if ($existing->status === 'completed') {
            $existingVideo = isset($existing->metadata['result_video_id'])
                ? SpotPlayerVideo::query()->find($existing->metadata['result_video_id'])
                : null;

            return response()->json([
                'ok' => true,
                'status' => $existing->target === 'teaser'
                    ? 'processing'
                    : ($existingVideo?->status === 'active' ? 'ready' : 'waiting_contract'),
                'video_id' => $existingVideo?->id,
            ]);
        }

        abort_unless($existing->status === 'uploading' && $existing->expires_at->isFuture(), 409);
        $this->authorizeTarget($request, $existing->target, (int) $existing->target_id, $existing->metadata ?? [], $workspace);

        $expectedChunks = (int) ($existing->metadata['total_chunks'] ?? 0);
        abort_unless($expectedChunks === (int) $data['total'], 422, 'تعداد تکه‌ها با اطلاعات شروع آپلود سازگار نیست.');

        $session = DB::transaction(function () use ($request, $data): VideoUploadSession {
            $locked = VideoUploadSession::query()
                ->where('upload_id', $data['upload_id'])
                ->where('user_id', $request->user()->id)
                ->where('status', 'uploading')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            abort_unless($locked, 409, 'نشست آپلود دیگر فعال نیست؛ بارگذاری را دوباره آغاز کنید.');

            $locked->update(['status' => 'processing']);

            return $locked->refresh();
        });

        $disk = Storage::disk('local');
        $chunkDirectory = "chunks/{$session->upload_id}";

        for ($index = 0; $index < $data['total']; $index++) {
            if (! $disk->exists("{$chunkDirectory}/{$index}.part")) {
                $session->update(['status' => 'uploading']);
                return $this->fail("تکه {$index} ناقص است؛ دوباره آپلود کن.");
            }
        }

        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        if (! in_array($extension, self::EXTENSIONS, true)) {
            $session->update(['status' => 'failed']);
            $disk->deleteDirectory($chunkDirectory);
            return $this->fail('فرمت فایل مجاز نیست (mp4, mov, mkv, webm).');
        }

        $destination = $session->target === 'teaser'
            ? 'uploads/'.Str::uuid().".{$extension}"
            : 'private/course-videos/'.Str::uuid().".{$extension}";

        try {
            $disk->makeDirectory(dirname($destination));
            $output = fopen($disk->path($destination), 'wb');
            if (! is_resource($output)) {
                throw new \RuntimeException('ساخت فایل نهایی آپلود ناموفق بود.');
            }

            for ($index = 0; $index < $data['total']; $index++) {
                $input = fopen($disk->path("{$chunkDirectory}/{$index}.part"), 'rb');
                if (! is_resource($input)) {
                    fclose($output);
                    throw new \RuntimeException("خواندن تکه {$index} ناموفق بود.");
                }
                stream_copy_to_stream($input, $output);
                fclose($input);
            }
            fclose($output);

            $actualBytes = filesize($disk->path($destination));
            if ($actualBytes === false || $actualBytes > $this->maxBytes($session->target)
                || $actualBytes !== (int) $session->bytes_uploaded
                || $actualBytes !== (int) ($session->metadata['total_bytes'] ?? 0)) {
                throw ValidationException::withMessages([
                    'file' => 'اندازه فایل نهایی با تکه‌های دریافت‌شده سازگار نیست یا از سقف مجاز بیشتر است.',
                ]);
            }

            $mime = function_exists('mime_content_type')
                ? (string) mime_content_type($disk->path($destination))
                : 'application/octet-stream';

            if (! str_starts_with($mime, 'video/') && $mime !== 'application/octet-stream') {
                throw ValidationException::withMessages(['file' => 'فایل ویدیو معتبر نیست.']);
            }

            if ($session->target === 'teaser') {
                $course = Course::query()->findOrFail($session->target_id);
                $oldOriginalPath = $course->teaser_original_path;

                DB::transaction(function () use ($course, $session, $destination): void {
                    $course->update([
                        'teaser_original_path' => $destination,
                        'teaser_status' => 'processing',
                        'teaser_error' => null,
                    ]);
                    $session->update(['status' => 'completed', 'completed_at' => now()]);
                    ProcessTeaserVideo::dispatch($course->id)->afterCommit();
                });

                if ($oldOriginalPath) {
                    $disk->delete($oldOriginalPath);
                }

                $disk->deleteDirectory($chunkDirectory);

                return response()->json(['ok' => true, 'status' => 'processing']);
            }

            $video = $videos->stageStoredFile(
                $session->metadata,
                $request->user(),
                $destination,
                $data['filename'],
                $mime,
                (int) $actualBytes
            );

            $session->update([
                'status' => 'completed',
                'completed_at' => now(),
                'metadata' => [...($session->metadata ?? []), 'result_video_id' => $video->id],
            ]);
            $disk->deleteDirectory($chunkDirectory);

            return response()->json([
                'ok' => true,
                'status' => $video->status === 'active' ? 'ready' : 'waiting_contract',
                'video_id' => $video->id,
            ]);
        } catch (Throwable $exception) {
            $disk->delete($destination);
            $disk->deleteDirectory($chunkDirectory);
            $session->update(['status' => 'failed']);
            throw $exception;
        }
    }

    public function cancel(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        $data = $request->validate(['upload_id' => ['required', 'uuid']]);

        $session = $this->findUploadSession($data['upload_id'], $request);
        abort_unless($session->status === 'uploading', 409, 'این نشست آپلود قبلاً بسته شده است.');

        $this->authorizeTarget($request, $session->target, (int) $session->target_id, $session->metadata ?? [], $workspace);

        Storage::disk('local')->deleteDirectory('chunks/'.$session->upload_id);
        $session->update(['status' => 'cancelled']);

        if ($session->target === 'teaser') {
            $course = Course::query()->find($session->target_id);
            if ($course) {
                $course->update([
                    'teaser_status' => filled($course->teaser_path) ? 'ready' : 'none',
                    'teaser_error' => null,
                ]);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeTarget(
        Request $request,
        string $target,
        int $courseId,
        array $metadata,
        ClassroomWorkspaceService $workspace
    ): Course {
        if ($target === 'teaser') {
            abort_unless($request->user()->hasPermissionTo('courses.manage'), 403);
            return Course::query()->findOrFail($courseId);
        }

        abort_unless($target === 'spotplayer_video', 404);
        abort_unless($request->user()->hasAnyPermission(['integrations.manage', 'videos.manage.assigned']), 403);

        $course = $workspace->coursesQueryFor($request->user())
            ->where(function ($query): void {
                $query->where(function ($legacy): void {
                    $legacy->whereNull('learning_features')->where('delivery_type', 'spotplayer');
                })->orWhere('learning_features->recorded_video', true);
            })
            ->findOrFail($courseId);

        if (filled($metadata['classroom_id'] ?? null)) {
            $workspace->queryFor($request->user())
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->findOrFail((int) $metadata['classroom_id']);
        }

        return $course;
    }

    private function findUploadSession(string $uploadId, Request $request): VideoUploadSession
    {
        $session = VideoUploadSession::query()->where('upload_id', $uploadId)->first();

        abort_unless($session, 410, 'نشست آپلود پیدا نشد یا از سامانه حذف شده است؛ لطفاً بارگذاری را دوباره آغاز کنید.');
        abort_unless((int) $session->user_id === (int) $request->user()->id, 404);

        return $session;
    }

    private function maxBytes(string $target): int
    {
        $megabytes = $target === 'teaser'
            ? (int) config('learning.max_teaser_mb')
            : (int) config('learning.max_lesson_mb');

        return $megabytes * 1024 * 1024;
    }

    private function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
