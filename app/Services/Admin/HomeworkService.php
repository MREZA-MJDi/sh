<?php

namespace App\Services\Admin;

use App\Models\Homework;
use App\Models\HomeworkSubmission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class HomeworkService
{
    public function create(array $data, int $createdBy): Homework
    {
        $path = null;

        if (($data['attachment'] ?? null) instanceof UploadedFile) {
            $path = $data['attachment']->store('private/homework', 'local');
        }

        try {
            return DB::transaction(fn () => Homework::create([
                'classroom_id' => $data['classroom_id'],
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'attachment_path' => $path,
                'status' => $data['status'],
                'created_by' => $createdBy,
            ]));
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            throw $exception;
        }
    }


    public function update(Homework $homework, array $data): Homework
    {
        $oldPath = $homework->attachment_path;
        $newPath = null;

        if (($data['attachment'] ?? null) instanceof UploadedFile) {
            $newPath = $data['attachment']->store('private/homework', 'local');
        }

        $removeAttachment = filter_var($data['remove_attachment'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $attachmentPath = $newPath ?? ($removeAttachment ? null : $oldPath);

        try {
            DB::transaction(fn () => $homework->update([
                'classroom_id' => $data['classroom_id'],
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => $data['status'],
            ]));
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }

        if ($oldPath && $oldPath !== $attachmentPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return $homework->refresh();
    }

    public function review(HomeworkSubmission $submission, array $data, int $reviewedBy): HomeworkSubmission
    {
        $submission->update([
            ...$data,
            'status' => 'reviewed',
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
        ]);

        return $submission->refresh();
    }
}
