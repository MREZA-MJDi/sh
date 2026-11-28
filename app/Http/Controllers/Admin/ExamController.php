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
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request, ClassroomWorkspaceService $classrooms, ExamWorkspaceService $exams): View
    {
        $filters = $request->validate([
            'course_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $user = $request->user();
        $examQuery = $exams->queryFor($user)
            ->when($filters['course_id'] ?? null, fn ($query, $id) => $query
                ->where(fn ($scope) => $scope->where('course_id', $id)
                    ->orWhere(fn ($classOnly) => $classOnly->whereNull('course_id')
                        ->whereHas('classroom', fn ($classroom) => $classroom->where('course_id', $id)))))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('starts_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('starts_at', '<=', $date));

        $stats = [
            'total' => (clone $examQuery)->count(),
            'upcoming' => (clone $examQuery)->whereIn('status', ['scheduled', 'published'])->count(),
            'quiz24' => (clone $examQuery)->where('provider', 'quiz24')->count(),
            'drafts' => (clone $examQuery)->where('status', 'draft')->count(),
        ];

        $records = (clone $examQuery)
            ->with(['classroom', 'course'])
            ->withCount([
                'results', 'questions', 'attempts',
                'attempts as pending_review_count' => fn ($attempts) => $attempts->where('status', 'pending_review'),
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $classroomOptions = $classrooms->queryFor($user)->orderBy('name')->get(['id', 'name']);
        $courseOptions = $classrooms->coursesQueryFor($user)->orderBy('title')->get(['id', 'title']);

        $teacherView = $classrooms->isTeacher($user);

        return view('admin.exams.index', [
            'exams' => $records,
            'classrooms' => $classroomOptions,
            'courses' => $courseOptions,
            'teacherView' => $teacherView,
            'stats' => $stats,
            'filters' => $filters,
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
    public function destroy(
        \Illuminate\Http\Request $request,
        Exam $exam,
        ExamWorkspaceService $workspace
    ): RedirectResponse {
        abort_unless($request->user()->hasRole('admin'), 403);
        abort_unless($workspace->owns($request->user(), $exam), 404);

        if ($exam->attempts()->exists() || $exam->results()->exists()) {
            return back()->withErrors(['exam' => 'این آزمون سابقه شرکت یا نتیجه دارد؛ برای حفظ سوابق امکان حذف آن وجود ندارد.']);
        }

        $exam->delete();

        return back()->with('success', 'آزمون حذف شد.');
    }

}
