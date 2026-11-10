<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitHomeworkRequest;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkSubmissionFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class HomeworkSubmissionController extends Controller
{
    public function store(SubmitHomeworkRequest $request, int $homework): RedirectResponse
    {
        $student = $request->user();
        $assignment = $this->accessibleHomework($student, $homework);
        $validated = $request->validated();

        $uploads = array_values(array_filter((array) ($validated['files'] ?? [])));
        if ($request->hasFile('attachment')) {
            $uploads[] = $request->file('attachment');
        }

        $storedPaths = [];

        try {
            foreach ($uploads as $upload) {
                $storedPaths[] = [
                    'path' => $upload->store("homework-submissions/{$student->id}/{$assignment->id}", 'local'),
                    'original_name' => Str::limit($upload->getClientOriginalName(), 255, ''),
                    'mime_type' => $upload->getMimeType(),
                    'size' => (int) $upload->getSize(),
                ];
            }

            DB::transaction(function () use ($assignment, $student, $validated, $storedPaths): void {
                $submission = HomeworkSubmission::query()->firstOrNew([
                    'homework_id' => $assignment->id,
                    'student_id' => $student->id,
                ]);

                abort_if(
                    $submission->exists && ($submission->reviewed_at !== null || $submission->status === 'graded'),
                    409,
                    'این تکلیف بررسی شده و دیگر امکان ویرایش پاسخ وجود ندارد.'
                );

                $submission->answer = trim((string) ($validated['answer'] ?? '')) ?: null;
                $submission->status = $assignment->due_at?->isPast() ? 'late' : 'submitted';
                $submission->submitted_at = now();
                $submission->save();

                foreach ($storedPaths as $file) {
                    $submission->files()->create($file);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete(array_column($storedPaths, 'path'));

            throw $exception;
        }

        return back()->with('status', 'پاسخ تکلیف با موفقیت ارسال شد.');
    }

    public function destroy(Request $request, int $homework): RedirectResponse
    {
        $assignment = $this->accessibleHomework($request->user(), $homework);
        $submission = HomeworkSubmission::query()
            ->where('homework_id', $assignment->id)
            ->where('student_id', $request->user()->id)
            ->with('files')
            ->firstOrFail();

        abort_if(
            $submission->reviewed_at !== null || $submission->status === 'graded',
            409,
            'پاسخ بررسی شده است و امکان حذف آن وجود ندارد.'
        );

        $paths = $submission->files->pluck('path')->all();
        $submission->delete();
        Storage::disk('local')->delete($paths);

        return back()->with('status', 'پاسخ تکلیف حذف شد.');
    }

    public function download(Request $request, HomeworkSubmissionFile $file): StreamedResponse
    {
        $file->loadMissing('submission');
        abort_unless($file->submission?->student_id === $request->user()->id, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    private function accessibleHomework(User $student, int $homeworkId): Homework
    {
        return Homework::query()
            ->where('status', 'published')
            ->whereHas('classroom', function ($classroom) use ($student): void {
                $classroom
                    ->where('classrooms.status', 'active')
                    ->whereHas('students', fn ($students) => $students->whereKey($student->id))
                    ->where(function ($access) use ($student): void {
                        $access->whereNull('classrooms.course_id')
                            ->orWhereHas('course', fn ($course) => $course
                                ->where('status', 'published')
                                ->whereHas('enrollments', fn ($enrollments) => $enrollments
                                    ->where('student_id', $student->id)
                                    ->where('status', 'active')
                                    ->where(fn ($expiry) => $expiry
                                        ->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now()))));
                    });
            })
            ->findOrFail($homeworkId);
    }
}
