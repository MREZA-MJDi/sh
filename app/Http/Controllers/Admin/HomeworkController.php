<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewHomeworkSubmissionRequest;
use App\Http\Requests\Admin\StoreHomeworkRequest;
use App\Http\Requests\Admin\UpdateHomeworkRequest;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkSubmissionFile;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\HomeworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class HomeworkController extends Controller
{
    public function index(Request $request, ClassroomWorkspaceService $workspace): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $user = $request->user();
        $classroomIds = $workspace->queryFor($user)->select('classrooms.id');

        $homeworks = Homework::query()
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('due_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('due_at', '<=', $date))
            ->with(['classroom', 'creator'])
            ->withCount([
                'submissions',
                'submissions as pending_submissions_count' => fn ($query) => $query->where('status', 'submitted'),
            ])
            ->when(
                $workspace->isTeacher($user),
                fn ($query) => $query->whereIn('classroom_id', $classroomIds)
            )
            ->latest()
            ->paginate(12)->withQueryString();

        $classrooms = $workspace->queryFor($user)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $submissions = HomeworkSubmission::query()
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('submitted_at', '<=', $date))
            ->with(['homework.classroom', 'student', 'files'])
            ->when(
                $workspace->isTeacher($user),
                fn ($query) => $query->whereHas(
                    'homework',
                    fn ($homeworkQuery) => $homeworkQuery->whereIn('classroom_id', $classroomIds)
                )
            )
            ->latest('submitted_at')
            ->paginate(15, ['*'], 'submissions')
            ->withQueryString();

        return view('admin.homework.index', compact('homeworks', 'classrooms', 'submissions', 'filters'));
    }

    public function store(
        StoreHomeworkRequest $request,
        HomeworkService $homework,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $data = $request->validated();
        $classroom = $workspace->queryFor($request->user())->with('course')->findOrFail($data['classroom_id']);
        if ($classroom->course && ! $classroom->course->featureEnabled('assignments')) {
            throw ValidationException::withMessages([
                'classroom_id' => 'قابلیت ثبت تکلیف برای دوره انتخاب‌شده غیرفعال است. ابتدا آن را از تنظیمات قابلیت‌های دوره فعال کنید.',
            ]);
        }
        $homework->create($data, (int) $request->user()->id);

        return back()->with('success', 'تکلیف با موفقیت ثبت شد.');
    }


    public function update(
        UpdateHomeworkRequest $request,
        Homework $homework,
        HomeworkService $service,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        abort_unless($workspace->queryFor($request->user())->whereKey($homework->classroom_id)->exists(), 403);

        $data = $request->validated();
        $classroom = $workspace->queryFor($request->user())->with('course')->findOrFail($data['classroom_id']);
        if ($classroom->course && ! $classroom->course->featureEnabled('assignments')) {
            throw ValidationException::withMessages([
                'classroom_id' => 'قابلیت ثبت تکلیف برای دوره انتخاب‌شده غیرفعال است. ابتدا آن را از تنظیمات قابلیت‌های دوره فعال کنید.',
            ]);
        }

        if ($homework->submissions()->exists() && (int) $data['classroom_id'] !== (int) $homework->classroom_id) {
            return back()->withInput()->withErrors(['classroom_id' => 'پس از دریافت پاسخ دانش‌آموز، انتقال تکلیف به کلاس دیگر مجاز نیست.']);
        }

        $service->update($homework, $data);

        return back()->with('success', 'تکلیف به‌روزرسانی شد.');
    }

    public function destroy(Homework $homework, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($workspace->queryFor(auth()->user())->whereKey($homework->classroom_id)->exists(), 403);

        if ($homework->submissions()->exists()) {
            return back()->withErrors(['homework' => 'این تکلیف پاسخ ثبت‌شده دارد؛ برای حفظ سوابق دانش‌آموزان حذف نمی‌شود.']);
        }

        if ($homework->attachment_path) {
            Storage::disk('local')->delete($homework->attachment_path);
        }

        $homework->delete();

        return back()->with('success', 'تکلیف حذف شد.');
    }

    public function review(
        ReviewHomeworkSubmissionRequest $request,
        HomeworkSubmission $submission,
        HomeworkService $homework,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $classroomId = $submission->homework()->value('classroom_id');
        abort_unless(
            $workspace->queryFor($request->user())->whereKey($classroomId)->exists(),
            403
        );

        $homework->review($submission, $request->validated(), (int) $request->user()->id);

        return back()->with('success', 'نتیجه بررسی تکلیف ذخیره شد.');
    }

    public function downloadSubmissionFile(
        HomeworkSubmissionFile $file,
        ClassroomWorkspaceService $workspace
    ) {
        $submission = $file->submission()->with('homework')->firstOrFail();

        abort_unless(
            $workspace->queryFor(auth()->user())->whereKey($submission->homework->classroom_id)->exists(),
            404
        );
        abort_unless(filled($file->path) && Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    public function downloadAttachment(Homework $homework, ClassroomWorkspaceService $workspace)
    {
        abort_unless(
            $workspace->queryFor(auth()->user())->whereKey($homework->classroom_id)->exists(),
            403
        );
        abort_unless(
            $homework->attachment_path && Storage::disk('local')->exists($homework->attachment_path),
            404
        );

        return Storage::disk('local')->download($homework->attachment_path);
    }
}
