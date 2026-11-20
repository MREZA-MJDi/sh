<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Learning\ProcessTeaserVideo;
use App\Models\Course;
use App\Models\ClassSession;
use App\Models\SpotPlayerVideo;
use App\Models\VideoUploadSession;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\SpotPlayer\SpotPlayerVideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
            'total_bytes' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'title' => ['required_if:target,spotplayer_video', 'nullable', 'string', 'max:180'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'class_session_id' => ['nullable', 'integer', 'exists:class_sessions,id'],
            'external_id' => ['nullable', 'string', 'max:190'],
        ]);

        $course = $this->authorizeTarget($request, $data['target'], (int) $data['target_id'], $data, $workspace);

        $chunkSizeBytes = $this->chunkSizeBytes();
        $totalChunks = (int) ceil((int) $data['total_bytes'] / $chunkSizeBytes);
        if ($totalChunks < 1 || $totalChunks > 2000) {
            throw ValidationException::withMessages(['file' => 'تعداد بخش‌های فایل از حد مجاز بیشتر است؛ اندازه فایل یا تنظیم قطعه آپلود را بررسی کنید.']);
        }

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

        $metadata = ['total_chunks' => $totalChunks, 'chunk_size_bytes' => $chunkSizeBytes, 'total_bytes' => (int) $data['total_bytes']];
        if ($data['target'] === 'spotplayer_video') {
            $metadata += [
                'title' => trim($data['title']),
                'course_id' => $course->id,
                'classroom_id' => filled($data['classroom_id'] ?? null) ? (int) $data['classroom_id'] : null,
                'class_session_id' => filled($data['class_session_id'] ?? null) ? (int) $data['class_session_id'] : null,
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
            'chunk_size_bytes' => $chunkSizeBytes,
            'total_chunks' => $totalChunks,
        ]);
    }

    public function chunk(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        if ($this->postMaxSizeExceeded($request)) {
            return $this->fail('درخواست از محدودیت post_max_size هاست بیشتر است؛ اندازه قطعه آپلود را کاهش دهید.', 413);
        }

        $data = $request->validate([
            'upload_id' => ['required', 'uuid'],
            'index' => ['required', 'integer', 'min:0', 'max:1999'],
        ]);

        $session = $this->findUploadSession($data['upload_id'], $request);
        abort_unless($session->status === 'uploading' && $session->expires_at->isFuture(), 409, 'نشست آپلود منقضی یا بسته شده است؛ بارگذاری را دوباره آغاز کنید.');

        $this->authorizeTarget($request, $session->target, (int) $session->target_id, $session->metadata ?? [], $workspace);

        $totalChunks = (int) ($session->metadata['total_chunks'] ?? 0);
        $chunkSizeBytes = (int) ($session->metadata['chunk_size_bytes'] ?? 0);
        $totalBytes = (int) ($session->metadata['total_bytes'] ?? 0);
        abort_unless($totalChunks > 0 && $chunkSizeBytes > 0 && $data['index'] < $totalChunks, 422, 'شماره تکه خارج از محدوده فایل است.');

        $file = $request->file('chunk');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return $this->fail($this->uploadErrorMessage($file), 413);
        }

        $expectedBytes = min($chunkSizeBytes, $totalBytes - ((int) $data['index'] * $chunkSizeBytes));
        if ($expectedBytes < 1 || (int) $file->getSize() !== $expectedBytes) {
            return $this->fail('اندازه این بخش با اندازه مورد انتظار فایل مطابقت ندارد؛ آپلود را از همین فایل دوباره ادامه دهید.', 422);
        }

        $disk = Storage::disk('local');
        $path = "chunks/{$data['upload_id']}/{$data['index']}.part";
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
                    : match ($existingVideo?->status) {
                        'active' => 'ready',
                        'processing' => 'processing',
                        'failed' => 'failed',
                        'waiting_contract' => 'waiting_contract',
                        default => 'failed',
                    },
                'video_id' => $existingVideo?->id,
            ]);
        }

        if ($existing->status === 'processing') {
            return response()->json([
                'ok' => true,
                'status' => 'processing',
                'video_id' => $existing->metadata['result_video_id'] ?? null,
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

        $expectedTotalBytes = (int) ($session->metadata['total_bytes'] ?? 0);
        $verifiedChunkBytes = 0;

        for ($index = 0; $index < $data['total']; $index++) {
            $chunkPath = "{$chunkDirectory}/{$index}.part";
            $expectedChunkBytes = min(
                (int) $session->metadata['chunk_size_bytes'],
                $expectedTotalBytes - ($index * (int) $session->metadata['chunk_size_bytes'])
            );

            if (! $disk->exists($chunkPath) || $expectedChunkBytes < 1 || $disk->size($chunkPath) !== $expectedChunkBytes) {
                $session->update(['status' => 'uploading']);
                return $this->fail("تکه {$index} ناقص یا خراب است؛ نشست و تکه‌های سالم حفظ شدند.");
            }

            $verifiedChunkBytes += $expectedChunkBytes;
        }

        if ($verifiedChunkBytes !== $expectedTotalBytes || (int) $session->bytes_uploaded !== $expectedTotalBytes) {
            $session->update(['status' => 'uploading']);
            return $this->fail('مجموع اندازه تکه‌ها معتبر نیست؛ تکه‌های موجود حفظ شدند.', 422);
        }

        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        if (! in_array($extension, self::EXTENSIONS, true)) {
            $session->update(['status' => 'uploading']);
            return $this->fail('فرمت فایل مجاز نیست (mp4, mov, mkv, webm, m4v).', 422);
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
                DB::transaction(function () use ($course, $session, $destination): void {
                    $lockedCourse = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

                    $lockedCourse->update([
                        'teaser_original_path' => $destination,
                        'teaser_status' => 'processing',
                        'teaser_error' => null,
                    ]);
                    $session->update(['status' => 'completed', 'completed_at' => now()]);
                    ProcessTeaserVideo::dispatch($lockedCourse->id, $destination)->afterCommit();
                });

                $disk->deleteDirectory($chunkDirectory);

                return response()->json(['ok' => true, 'status' => 'processing']);
            }

            $video = DB::transaction(function () use ($videos, $session, $request, $destination, $data, $mime, $actualBytes): SpotPlayerVideo {
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

                return $video;
            });
            $disk->deleteDirectory($chunkDirectory);

            return response()->json([
                'ok' => true,
                'status' => match ($video->status) { 'active' => 'ready', 'processing' => 'processing', 'failed' => 'failed', default => 'waiting_contract' },
                'video_id' => $video->id,
            ]);
        } catch (Throwable $exception) {
            // A committed completion is authoritative even if cleanup/response handling fails.
            $latestSession = $session->fresh();

            if ($latestSession && $latestSession->status !== 'completed') {
                $disk->delete($destination);

                // Keep all uploaded chunks so transient storage/DB errors are recoverable.
                $latestSession->update(['status' => 'uploading']);
            }

            throw $exception;
        }
    }

    public function resume(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        $data = $request->validate(['upload_id' => ['required', 'uuid']]);
        $session = $this->findUploadSession($data['upload_id'], $request);

        $this->authorizeTarget($request, $session->target, (int) $session->target_id, $session->metadata ?? [], $workspace);

        $total = (int) ($session->metadata['total_chunks'] ?? 0);
        if (in_array($session->status, ['processing', 'completed'], true)) {
            return response()->json([
                'ok' => true,
                'status' => $session->status,
                'upload_id' => $session->upload_id,
                'total_chunks' => $total,
                'chunk_size_bytes' => (int) ($session->metadata['chunk_size_bytes'] ?? 0),
                // Treat finalizing/completed sessions as fully received so the client
                // can safely repeat the idempotent complete request after a lost response.
                'received_chunks' => $total > 0 ? range(0, $total - 1) : [],
            ]);
        }

        abort_unless($session->status === 'uploading' && $session->expires_at->isFuture(), 410, 'نشست آپلود دیگر قابل ادامه نیست؛ یک آپلود تازه آغاز کنید.');

        $disk = Storage::disk('local');
        $received = [];
        $verifiedBytes = 0;
        $chunkSize = (int) ($session->metadata['chunk_size_bytes'] ?? 0);
        $totalBytes = (int) ($session->metadata['total_bytes'] ?? 0);

        for ($index = 0; $index < $total; $index++) {
            $path = "chunks/{$session->upload_id}/{$index}.part";
            $expectedBytes = min($chunkSize, $totalBytes - ($index * $chunkSize));

            if ($expectedBytes > 0 && $disk->exists($path) && $disk->size($path) === $expectedBytes) {
                $received[] = $index;
                $verifiedBytes += $expectedBytes;
            } elseif ($disk->exists($path)) {
                // A truncated/partial chunk must be uploaded again, not skipped by the browser.
                $disk->delete($path);
            }
        }

        $session->update([
            'bytes_uploaded' => $verifiedBytes,
            'expires_at' => now()->addHours(2),
        ]);

        return response()->json([
            'ok' => true,
            'upload_id' => $session->upload_id,
            'total_chunks' => $total,
            'chunk_size_bytes' => (int) ($session->metadata['chunk_size_bytes'] ?? 0),
            'received_chunks' => $received,
        ]);
    }

    public function status(Request $request, ClassroomWorkspaceService $workspace): JsonResponse
    {
        $data = $request->validate(['upload_id' => ['required', 'uuid']]);
        $session = $this->findUploadSession($data['upload_id'], $request);
        $this->authorizeTarget($request, $session->target, (int) $session->target_id, $session->metadata ?? [], $workspace);

        if ($session->target === 'teaser') {
            $course = Course::query()->findOrFail($session->target_id);

            return response()->json([
                'status' => $course->teaser_status,
                'error' => $course->teaser_error,
            ]);
        }

        $video = isset($session->metadata['result_video_id'])
            ? SpotPlayerVideo::query()->find($session->metadata['result_video_id'])
            : null;

        return response()->json([
            'status' => match ($video?->status) {
                'active' => 'ready',
                'processing' => 'processing',
                'failed' => 'failed',
                'waiting_contract' => 'waiting_contract',
                default => match ($session->status) {
                    'uploading' => 'uploading',
                    'processing' => 'processing',
                    default => 'failed',
                },
            },
            'error' => $video?->processing_error,
            'video_id' => $video?->id,
        ]);
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
        $isExternalSpotPlayerAsset = filled($metadata['external_id'] ?? null);
        abort_unless($request->user()->hasAnyPermission(['courses.manage', 'videos.manage.assigned', 'integrations.manage']), 403);
        if ($isExternalSpotPlayerAsset) {
            abort_unless($request->user()->hasAnyPermission(['integrations.manage', 'courses.manage']), 403);
        }

        $course = $workspace->coursesQueryFor($request->user())
            ->where(function ($query): void {
                $query->where(function ($legacy): void {
                    $legacy->whereNull('learning_features->recorded_video')->where('delivery_type', 'spotplayer');
                })->orWhere('learning_features->recorded_video', true);
            })
            ->findOrFail($courseId);

        $classroomId = filled($metadata['classroom_id'] ?? null)
            ? (int) $metadata['classroom_id']
            : null;

        if (filled($metadata['class_session_id'] ?? null)) {
            $classSession = ClassSession::query()
                ->whereKey((int) $metadata['class_session_id'])
                ->where('status', '!=', 'cancelled')
                ->whereHas('classroom', fn ($classrooms) => $classrooms->where('course_id', $course->id))
                ->firstOrFail();

            abort_if($classroomId && (int) $classSession->classroom_id !== $classroomId, 422, 'جلسه انتخاب‌شده متعلق به کلاس انتخاب‌شده نیست.');

            $workspace->queryFor($request->user())
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->findOrFail((int) $classSession->classroom_id);
        } elseif ($classroomId) {
            $workspace->queryFor($request->user())
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->findOrFail($classroomId);
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

    private function chunkSizeBytes(): int
    {
        $configured = max(1, (int) config('learning.chunk_mb')) * 1024 * 1024 - 65536;
        $limits = [$configured];
        $uploadLimit = $this->iniSizeToBytes((string) ini_get('upload_max_filesize'));
        $postLimit = $this->iniSizeToBytes((string) ini_get('post_max_size'));

        if ($uploadLimit > 0) {
            $limits[] = max(262144, $uploadLimit - 65536);
        }
        if ($postLimit > 0) {
            $limits[] = max(262144, $postLimit - 262144);
        }

        return max(262144, min($limits));
    }

    private function postMaxSizeExceeded(Request $request): bool
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postLimit = $this->iniSizeToBytes((string) ini_get('post_max_size'));

        return $contentLength > 0 && $postLimit > 0 && $contentLength > $postLimit;
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function uploadErrorMessage(?UploadedFile $file): string
    {
        return match ($file?->getError()) {
            UPLOAD_ERR_INI_SIZE => 'اندازه قطعه از upload_max_filesize هاست بیشتر است؛ قطعه کوچک‌تری انتخاب کنید.',
            UPLOAD_ERR_FORM_SIZE => 'اندازه قطعه از محدودیت فرم بیشتر است.',
            UPLOAD_ERR_PARTIAL => 'ارسال قطعه ناقص شد؛ اتصال اینترنت را بررسی و دوباره تلاش کنید.',
            UPLOAD_ERR_NO_FILE => 'قطعه‌ای به سرور نرسید؛ دوباره تلاش کنید.',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت آپلود روی هاست در دسترس نیست.',
            UPLOAD_ERR_CANT_WRITE => 'هاست نتوانست فایل موقت آپلود را ذخیره کند؛ فضای دیسک را بررسی کنید.',
            UPLOAD_ERR_EXTENSION => 'یک افزونه PHP جلوی آپلود فایل را گرفته است.',
            default => 'قطعه فایل به‌درستی دریافت نشد؛ تنظیمات آپلود هاست را بررسی کنید.',
        };
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
