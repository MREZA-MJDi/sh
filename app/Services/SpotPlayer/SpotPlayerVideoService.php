<?php

namespace App\Services\SpotPlayer;

use App\Jobs\Learning\ProcessCourseRecordingVideo;
use App\Models\SpotPlayerVideo;
use App\Models\ClassSession;
use App\Models\User;
use App\Notifications\CourseVideoSubmittedForReview;
use Illuminate\Support\Facades\Notification;
use App\Services\Integrations\IntegrationOperationService;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class SpotPlayerVideoService
{
    public function __construct(
        private readonly IntegrationOperationService $operations,
        private readonly ClassroomWorkspaceService $workspace,
    ) {}


    /**
     * Stages a file already assembled by the resumable uploader.
     * The provider request remains pending until the official SpotPlayer contract is installed.
     */
    public function stageStoredFile(
        array $data,
        User $actor,
        string $storagePath,
        string $originalName,
        string $mimeType,
        int $size,
    ): SpotPlayerVideo {
        $course = $this->workspace->coursesQueryFor($actor)->findOrFail((int) $data['course_id']);
        if (! $course->featureEnabled('recorded_video')) {
            throw new RuntimeException('امکان ویدیوهای آموزشی برای این دوره فعال نیست.');
        }

        $classroomId = filled($data['classroom_id'] ?? null) ? (int) $data['classroom_id'] : null;
        $classSessionId = filled($data['class_session_id'] ?? null) ? (int) $data['class_session_id'] : null;

        if ($classSessionId) {
            $classSession = ClassSession::query()
                ->whereKey($classSessionId)
                ->where('status', '!=', 'cancelled')
                ->whereHas('classroom', fn ($classrooms) => $classrooms->where('course_id', $course->id))
                ->firstOrFail();

            abort_if($classroomId && (int) $classSession->classroom_id !== $classroomId, 422, 'جلسه انتخاب‌شده متعلق به کلاس انتخاب‌شده نیست.');
            $classroomId = (int) $classSession->classroom_id;
        }

        if ($classroomId) {
            $this->workspace->queryFor($actor)
                ->where('course_id', $course->id)
                ->where('status', 'active')
                ->findOrFail($classroomId);
        }

        abort_unless(Storage::disk('local')->exists($storagePath), 404);

        $sha256 = hash_file('sha256', Storage::disk('local')->path($storagePath));
        if (! is_string($sha256)) {
            throw new RuntimeException('محاسبه checksum فایل ویدیو انجام نشد.');
        }

        $video = DB::transaction(function () use ($data, $actor, $course, $classroomId, $classSessionId, $storagePath, $originalName, $mimeType, $size, $sha256): SpotPlayerVideo {
            $video = SpotPlayerVideo::query()->create([
                'title' => trim($data['title']),
                'course_id' => $course->id,
                'classroom_id' => $classroomId,
                'class_session_id' => $classSessionId,
                'external_id' => filled($data['external_id'] ?? null) ? trim($data['external_id']) : null,
                'embed_url' => null,
                'status' => filled($data['external_id'] ?? null) ? 'waiting_contract' : 'processing',
                'approval_status' => $actor->hasRole('teacher') && ! $actor->hasRole('admin') ? 'pending' : 'approved',
                'created_by' => $actor->id,
                'file_path' => $storagePath,
                'file_original_name' => mb_substr($originalName, 0, 255),
                'file_mime_type' => $mimeType ?: 'application/octet-stream',
                'file_size_bytes' => $size,
                'file_sha256' => $sha256,
                'staged_at' => now(),
            ]);

            if (filled($video->external_id)) {
                $this->operations->waitForContract(
                    provider: 'spotplayer',
                    action: 'upload_video',
                    entity: $video,
                    safeRequestMeta: [
                        'video_id' => $video->id,
                        'local_course_id' => $video->course_id,
                        'local_classroom_id' => $video->classroom_id,
                        'storage_disk' => 'local',
                        'storage_path' => $storagePath,
                        'original_name' => $video->file_original_name,
                        'mime_type' => $video->file_mime_type,
                        'file_size_bytes' => $video->file_size_bytes,
                        'sha256' => $video->file_sha256,
                        'external_video_id_supplied' => filled($video->external_id),
                        'upload_mode' => 'chunked',
                    ],
                    idempotencyKey: 'spotplayer:upload-video:'.$video->id.':'.$sha256,
                );
            }

            return $video->refresh();
        });

        $this->notifyAdminsForReview($video, $actor);
        if (blank($video->external_id)) {
            $this->dispatchProcessing($video);
        }

        return $video;
    }

    public function stage(array $data, User $actor): SpotPlayerVideo
    {
        $uploadedFile = $data['file'] ?? null;

        if (! ($uploadedFile instanceof UploadedFile)) {
            $video = DB::transaction(function () use ($data, $actor): SpotPlayerVideo {
                $externalId = trim((string) ($data['external_id'] ?? ''));

                if ($externalId === '') {
                    throw new RuntimeException('برای ثبت ویدئوی موجود در SpotPlayer، شناسه خارجی الزامی است.');
                }

                $video = SpotPlayerVideo::create([
                    'title' => $data['title'],
                    'course_id' => $data['course_id'],
                    'classroom_id' => $data['classroom_id'] ?? null,
                    'class_session_id' => $data['class_session_id'] ?? null,
                    'external_id' => $externalId,
                    'embed_url' => null,
                    'status' => filled($data['external_id'] ?? null) ? 'waiting_contract' : 'active',
                    'approval_status' => $actor->hasRole('teacher') && ! $actor->hasRole('admin') ? 'pending' : 'approved',
                    'created_by' => $actor->id,
                    'file_path' => null,
                    'file_original_name' => null,
                    'file_mime_type' => null,
                    'file_size_bytes' => null,
                    'file_sha256' => null,
                    'staged_at' => null,
                ]);

                if (filled($video->external_id)) {
                    $this->operations->waitForContract(
                            provider: 'spotplayer',
                            action: 'register_existing_video',
                            entity: $video,
                            safeRequestMeta: [
                                'video_id' => $video->id,
                                'local_course_id' => $video->course_id,
                                'local_classroom_id' => $video->classroom_id,
                                'external_video_id' => $externalId,
                                'source' => 'existing_spotplayer_asset',
                            ],
                            idempotencyKey: 'spotplayer:register-video:'.$video->id.':'.sha1($externalId),
                        );
                }

                return $video->refresh();
            });
            $this->notifyAdminsForReview($video, $actor);
            return $video;
        }

        if (filled($data['external_id'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'external_id' => 'فایل محلی و شناسه SpotPlayer را جداگانه ثبت کنید.',
            ]);
        }

        $storagePath = $uploadedFile->store('private/course-videos', 'local');
        if (! $storagePath) {
            throw new RuntimeException('ذخیره فایل ویدیویی در فضای خصوصی انجام نشد.');
        }

        try {
            return $this->stageStoredFile(
                $data, $actor, $storagePath, $uploadedFile->getClientOriginalName(),
                $uploadedFile->getMimeType() ?: 'application/octet-stream', (int) $uploadedFile->getSize(),
            );
        } catch (Throwable $exception) {
            // Do not delete a file already referenced by a committed video.
            if (! SpotPlayerVideo::query()->where('file_path', $storagePath)->exists()) {
                Storage::disk('local')->delete($storagePath);
            }
            throw $exception;
        }
    }

    private function notifyAdminsForReview(SpotPlayerVideo $video, User $actor): void
    {
        if (! $actor->hasRole('teacher') || $actor->hasRole('admin')) {
            return;
        }

        $notification = new CourseVideoSubmittedForReview($video->loadMissing('creator'));

        User::query()
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'admin'))
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, fn ($admins) => Notification::send($admins, $notification));
    }

    private function dispatchProcessing(SpotPlayerVideo $video): void
    {
        try {
            ProcessCourseRecordingVideo::dispatch($video->id);
        } catch (Throwable $exception) {
            $video->update([
                'status' => 'failed',
                'processing_error' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
        }
    }
}
