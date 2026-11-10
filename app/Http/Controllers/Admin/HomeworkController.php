<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewHomeworkSubmissionRequest;
use App\Http\Requests\Admin\StoreHomeworkRequest;
use App\Models\Classroom;
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
        $teacherView = $user->hasRole('teacher') && ! $user->hasRole('admin');
        $homeworks = Homework::with(['classroom', 'creator'])
            ->withCount([
                'submissions',
                'submissions as pending_submissions_count' => fn ($query) => $query->where('status', 'submitted'),
            ])
            ->when($teacherView, fn ($query) => $query->whereHas('classroom', fn ($classes) => $classes->where('teacher_id', $user->id)))
            ->latest()->paginate(12);

        $classrooms = $workspace->queryFor($user)->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $submissions = HomeworkSubmission::with(['homework.classroom', 'student'])
            ->when($teacherView, fn ($query) => $query->whereHas('homework.classroom', fn ($classes) => $classes->where('teacher_id', $user->id)))
            ->latest('submitted_at')->limit(30)->get();

        return view('admin.homework.index', compact('homeworks', 'classrooms', 'submissions'));
    }

    public function store(StoreHomeworkRequest $request, HomeworkService $homework, ClassroomWorkspaceService $workspace): RedirectResponse
    {
        $data = $request->validated();
        $workspace->queryFor($request->user())->findOrFail($data['classroom_id']);
        $homework->create($data, (int) $request->user()->id);

        return back()->with('success', 'تکلیف با موفقیت ثبت شد.');
    }

    public function review(
        ReviewHomeworkSubmissionRequest $request,
        HomeworkSubmission $submission,
        HomeworkService $homework
    ): RedirectResponse {
        if ($request->user()->hasRole('teacher') && ! $request->user()->hasRole('admin')) {
            abort_unless($submission->homework()->whereHas('classroom', fn ($query) => $query->where('teacher_id', $request->user()->id))->exists(), 403);
        }

        $homework->review($submission, $request->validated(), (int) $request->user()->id);

        return back()->with('success', 'نتیجه بررسی تکلیف ذخیره شد.');
    }

    public function downloadAttachment(Homework $homework)
    {
        if (auth()->user()->hasRole('teacher') && ! auth()->user()->hasRole('admin')) {
            abort_unless($homework->classroom()->where('teacher_id', auth()->id())->exists(), 403);
        }
        abort_unless($homework->attachment_path && Storage::disk('local')->exists($homework->attachment_path), 404);

        return Storage::disk('local')->download($homework->attachment_path);
    }
}
