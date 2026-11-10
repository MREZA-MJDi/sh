<?php

namespace App\Services\Admin;

use App\Models\LearningResource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class LearningResourceService
{
    public function create(array $data, User $actor): LearningResource
    {
        $path = $this->storeFile($data['file'] ?? null);

        try {
            return DB::transaction(function () use ($data, $actor, $path): LearningResource {
                $isFree = (bool) ($data['is_free'] ?? false);

                return LearningResource::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'kind' => $data['kind'],
                    'course_id' => $data['course_id'] ?? null,
                    'classroom_id' => $data['classroom_id'] ?? null,
                    'is_free' => $isFree,
                    'price' => $isFree ? 0 : (int) ($data['price'] ?? 0),
                    'visibility' => $data['visibility'],
                    'file_path' => $path,
                    'status' => $data['status'],
                    'uploaded_by' => $actor->id,
                ]);
            });
        } catch (Throwable $exception) {
            $this->deleteFile($path);
            throw $exception;
        }
    }

    public function update(LearningResource $resource, array $data): LearningResource
    {
        $newPath = $this->storeFile($data['file'] ?? null);
        $previousPath = $resource->file_path;

        try {
            DB::transaction(function () use ($resource, $data, $newPath): void {
                $isFree = (bool) ($data['is_free'] ?? false);

                $resource->update([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'kind' => $data['kind'],
                    'course_id' => $data['course_id'] ?? null,
                    'classroom_id' => $data['classroom_id'] ?? null,
                    'is_free' => $isFree,
                    'price' => $isFree ? 0 : (int) ($data['price'] ?? 0),
                    'visibility' => $data['visibility'],
                    'file_path' => $newPath ?: $resource->file_path,
                    'status' => $data['status'],
                ]);
            });
        } catch (Throwable $exception) {
            $this->deleteFile($newPath);
            throw $exception;
        }

        if ($newPath) {
            $this->deleteFile($previousPath);
        }

        return $resource->refresh();
    }

    public function delete(LearningResource $resource): void
    {
        $path = $resource->file_path;
        DB::transaction(fn () => $resource->delete());
        $this->deleteFile($path);
    }

    private function storeFile(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $path = $file->store('private/resources', 'local');
        if (! $path) {
            throw new RuntimeException('ذخیره فایل در فضای خصوصی انجام نشد.');
        }

        return $path;
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}
