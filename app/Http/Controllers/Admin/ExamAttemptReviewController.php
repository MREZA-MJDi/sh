<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\Admin\ExamWorkspaceService;
use App\Services\StudentExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ExamAttemptReviewController extends Controller
{
    public function index(Exam $exam, ExamWorkspaceService $workspace): View
    {
        abort_unless($workspace->owns(auth()->user(), $exam), 404);
        $attempts = $exam->attempts()
            ->with(['student:id,name', 'answers.question', 'reviewer:id,name'])
            ->whereIn('status', ['pending_review', 'graded'])
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.exams.attempts', compact('exam', 'attempts'));
    }

    public function grade(
        Request $request,
        Exam $exam,
        ExamAttempt $attempt,
        ExamWorkspaceService $workspace,
        StudentExamService $exams
    ): RedirectResponse {
        abort_unless($workspace->owns($request->user(), $exam), 404);
        abort_unless((int) $attempt->exam_id === (int) $exam->id, 404);
        abort_unless($attempt->status === 'pending_review', 422);

        $data = $request->validate([
            'scores' => ['required', 'array', 'max:100'],
            'scores.*' => ['required', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string', 'max:10000'],
        ]);

        $exams->recordManualReview(
            $attempt,
            $request->user(),
            $data['scores'],
            $data['feedback'] ?? null
        );

        return back()->with('success', 'نمره و بازخورد آزمون ثبت شد و نتیجه در کارتابل دانش‌آموز قابل مشاهده است.');
    }
}
