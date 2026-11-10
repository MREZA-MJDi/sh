<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQuiz24ExamRequest;
use App\Http\Requests\Admin\UpdateExamRequest;
use App\Models\Exam;
use App\Services\Admin\ClassroomWorkspaceService;
use App\Services\Admin\ExamManagementService;
use App\Services\Admin\ExamWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(ClassroomWorkspaceService $classrooms, ExamWorkspaceService $exams): View
    {
        $user = auth()->user();
        $records = $exams->queryFor($user)->with(['classroom', 'course'])
            ->withCount('results')->latest()->paginate(15);
        $classroomOptions = $classrooms->queryFor($user)->orderBy('name')->get(['id', 'name']);
        $courseOptions = $classrooms->coursesQueryFor($user)->orderBy('title')->get(['id', 'title']);

        return view('admin.exams.index', [
            'exams' => $records,
            'classrooms' => $classroomOptions,
            'courses' => $courseOptions,
        ]);
    }

    public function update(
        UpdateExamRequest $request,
        Exam $exam,
        ExamManagementService $management,
        ExamWorkspaceService $workspace
    ): RedirectResponse {
        abort_unless($workspace->owns($request->user(), $exam), 403);
        $management->update($exam, $request->validated(), $request->user());

        return back()->with('success', 'آزمون به‌روزرسانی شد؛ در صورت استفاده از Quiz24، درخواست مربوط در کارتابل ثبت می‌شود.');
    }

    public function results(Exam $exam, ExamWorkspaceService $workspace): View
    {
        abort_unless($workspace->owns(auth()->user(), $exam), 403);
        $results = $exam->results()->with('student')->latest('synced_at')->paginate(30);

        return view('admin.exams.results', compact('exam', 'results'));
    }

    public function store(
        StoreQuiz24ExamRequest $request,
        ExamManagementService $management
    ): RedirectResponse {
        $management->create($request->validated(), $request->user());

        return back()->with('success', 'آزمون ثبت شد؛ در صورت استفاده از Quiz24، درخواست اتصال در کارتابل قرار گرفت.');
    }
}
