<?php

namespace App\Services\SpotPlayer;

use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\Integrations\IntegrationOperationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class SpotPlayerVideoService
{
    public function __construct(private readonly IntegrationOperationService $operations) {}

    public function stage(array $data, User $actor): SpotPlayerVideo
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $data['file'];
        $storagePath = $uploadedFile->store('private/spotplayer-staging', 'local');

        if (! $storagePath) {
            throw new RuntimeException('ذخیره فایل ویدیویی در فضای خصوصی انجام نشد.');
        }

        try {
            $sha256 = hash_file('sha256', Storage::disk('local')->path($storagePath));
            if (! is_string($sha256)) {
                throw new RuntimeException('محاسبه checksum فایل ویدیو انجام نشد.');
            }

            return DB::transaction(function () use ($data, $actor, $uploadedFile, $storagePath, $sha256): SpotPlayerVideo {
                $video = SpotPlayerVideo::create([
                    'title' => $data['title'],
                    'course_id' => $data['course_id'],
                    'classroom_id' => $data['classroom_id'] ?? null,
                    'external_id' => $data['external_id'] ?? null,
                    'embed_url' => $data['embed_url'] ?? null,
                    'status' => 'waiting_contract',
                    'created_by' => $actor->id,
                    'file_path' => $storagePath,
                    'file_original_name' => mb_substr($uploadedFile->getClientOriginalName(), 0, 255),
                    'file_mime_type' => $uploadedFile->getMimeType() ?: 'application/octet-stream',
                    'file_size_bytes' => $uploadedFile->getSize(),
                    'file_sha256' => $sha256,
                    'staged_at' => now(),
                ]);

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
                    ],
                    idempotencyKey: 'spotplayer:upload-video:'.$video->id.':'.$sha256,
                );

                return $video->refresh();
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storagePath);
            throw $exception;
        }
    }
}
