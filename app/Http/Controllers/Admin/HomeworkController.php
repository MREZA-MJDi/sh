<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewHomeworkSubmissionRequest;
use App\Http\Requests\Admin\StoreHomeworkRequest;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\HomeworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeworkController extends Controller
{
    public function index(ClassroomWorkspaceService $workspace): View
    {
        $user = auth()->user();
        $classroomIds = $workspace->queryFor($user)->select('classrooms.id');

        $homeworks = Homework::query()
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
            ->paginate(12);

        $classrooms = $workspace->queryFor($user)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $submissions = HomeworkSubmission::query()
            ->with(['homework.classroom', 'student', 'files'])
            ->when(
                $workspace->isTeacher($user),
                fn ($query) => $query->whereHas(
                    'homework',
                    fn ($homeworkQuery) => $homeworkQuery->whereIn('classroom_id', $classroomIds)
                )
            )
            ->latest('submitted_at')
            ->limit(30)
            ->get();

        return view('admin.homework.index', compact('homeworks', 'classrooms', 'submissions'));
    }

    public function store(
        StoreHomeworkRequest $request,
        HomeworkService $homework,
        ClassroomWorkspaceService $workspace
    ): RedirectResponse {
        $data = $request->validated();
        $workspace->queryFor($request->user())->findOrFail($data['classroom_id']);
        $homework->create($data, (int) $request->user()->id);

        return back()->with('success', 'تکلیف با موفقیت ثبت شد.');
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
