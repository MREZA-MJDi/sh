<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreHomeworkSubmissionRequest;
use App\Models\Classroom;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkSubmissionFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class HomeworkSubmissionController extends Controller
{
    public function store(
        StoreHomeworkSubmissionRequest $request,
        Homework $homework
    ): RedirectResponse {
        $student = $request->user();
        $homework = $this->availableHomework($student, $homework);
        $data = $request->validated();

        $submission = HomeworkSubmission::query()
            ->where('homework_id', $homework->id)
            ->where('student_id', $student->id)
            ->withCount('files')
            ->first();

        $this->ensureEditable($submission);

        $incomingFiles = $request->file('files', []);
        if (blank($data['answer'] ?? null) && $incomingFiles === [] && ! ($submission?->files_count > 0)) {
            throw ValidationException::withMessages([
                'answer' => 'متن پاسخ یا دست‌کم یک فایل پیوست وارد کنید.',
            ]);
        }

        if (($submission?->files_count ?? 0) + count($incomingFiles) > 5) {
            throw ValidationException::withMessages([
                'files' => 'حداکثر پنج فایل برای هر تکلیف قابل ثبت است.',
            ]);
        }

        $storedFiles = [];

        try {
            foreach ($incomingFiles as $file) {
                $path = $file->store(
                    'private/submissions/'.$student->id.'/'.$homework->id,
                    'local'
                );

                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('ذخیره‌ی فایل پاسخ تکلیف ناموفق بود.');
                }

                $storedFiles[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }

            DB::transaction(function () use ($homework, $student, $data, $submission, $storedFiles): void {
                $submission = $submission
                    ? tap($submission)->update([
                        'answer' => $data['answer'] ?? null,
                        'status' => 'submitted',
                        'submitted_at' => now(),
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'score' => null,
                        'feedback' => null,
                    ])
                    : HomeworkSubmission::query()->create([
                        'homework_id' => $homework->id,
                        'student_id' => $student->id,
                        'answer' => $data['answer'] ?? null,
                        'status' => 'submitted',
                        'submitted_at' => now(),
                    ]);

                foreach ($storedFiles as $storedFile) {
                    $submission->files()->create($storedFile);
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedFiles as $storedFile) {
                Storage::disk('local')->delete($storedFile['path']);
            }

            throw $exception;
        }

        return back()->with('status', 'پاسخ تکلیف با موفقیت ثبت شد.');
    }

    public function destroy(Request $request, Homework $homework): RedirectResponse
    {
        $student = $request->user();
        $homework = $this->availableHomework($student, $homework);

        $submission = HomeworkSubmission::query()
            ->where('homework_id', $homework->id)
            ->where('student_id', $student->id)
            ->with('files')
            ->firstOrFail();

        $this->ensureEditable($submission);

        $paths = $submission->files->pluck('path')
            ->push($submission->attachment_path)
            ->filter()
            ->values()
            ->all();

        $submission->delete();
        Storage::disk('local')->delete($paths);

        return back()->with('status', 'پاسخ تکلیف حذف شد.');
    }

    public function downloadFile(
        Request $request,
        HomeworkSubmissionFile $file
    ): StreamedResponse {
        $submission = $file->submission()->with('homework')->firstOrFail();
        $homework = $this->availableHomework($request->user(), $submission->homework);

        abort_unless(
            $submission->student_id === $request->user()->id
                && $submission->homework_id === $homework->id
                && Storage::disk('local')->exists($file->path),
            404
        );

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function destroyFile(
        Request $request,
        HomeworkSubmissionFile $file
    ): RedirectResponse {
        $submission = $file->submission()->with('homework')->firstOrFail();
        $homework = $this->availableHomework($request->user(), $submission->homework);

        abort_unless($submission->student_id === $request->user()->id, 404);
        $this->ensureEditable($submission);

        Storage::disk('local')->delete($file->path);
        $file->delete();

        return back()->with('status', 'فایل پیوست حذف شد.');
    }

    private function availableHomework(User $student, Homework $homework): Homework
    {
        return Homework::query()
            ->whereKey($homework->getKey())
            ->where('status', 'published')
            ->whereHas('classroom', function (Builder $classroom) use ($student): void {
                $classroom
                    ->where('classrooms.status', 'active')
                    ->whereHas('students', fn (Builder $students) => $students->whereKey($student->id))
                    ->where(function (Builder $access) use ($student): void {
                        $access->whereNull('classrooms.course_id')
                            ->orWhereHas('course', fn (Builder $course) => $course
                                ->where('status', 'published')
                                ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                                    ->where('student_id', $student->id)
                                    ->where('status', 'active')
                                    ->where(fn (Builder $expiry) => $expiry
                                        ->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now()))));
                    });
            })
            ->firstOrFail();
    }

    private function ensureEditable(?HomeworkSubmission $submission): void
    {
        abort_if(
            $submission && (
                $submission->reviewed_at !== null
                || in_array($submission->status, ['reviewed', 'graded'], true)
            ),
            422,
            'پس از بررسی پاسخ توسط مدرس، امکان ویرایش یا حذف آن وجود ندارد.'
        );
    }
}
