<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\SubmitInternalExamRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\StudentExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ExamController extends Controller
{
    public function index(Request $request, StudentExamService $exams): View
    {
        return view('student.exams', [
            'exams' => $exams->query($request->user())->paginate(12)->withQueryString(),
        ]);
    }

    public function show(Request $request, Exam $exam, StudentExamService $exams): View
    {
        return view('student.exam-detail', ['exam' => $exams->find($request->user(), $exam)]);
    }

    public function start(Request $request, Exam $exam, StudentExamService $exams): RedirectResponse
    {
        $attempt = $exams->start($request->user(), $exam);

        return redirect()->route('student.exams.attempt', ['exam' => $exam, 'attempt' => $attempt]);
    }

    public function attempt(Request $request, Exam $exam, ExamAttempt $attempt, StudentExamService $exams): View|RedirectResponse
    {
        abort_unless((int) $attempt->student_id === (int) $request->user()->id, 404);
        abort_unless((int) $attempt->exam_id === (int) $exam->id, 404);
        abort_unless($exams->canAccess($request->user(), $exam), 404);

        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.exams.result', $exam)->with('status', 'این تلاش قبلاً ثبت شده است.');
        }

        $attempt->load(['exam.questions' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'), 'answers']);

        return view('student.exam-take', [
            'attempt' => $attempt,
            'exam' => $attempt->exam,
            'questions' => $attempt->exam->questions,
            'answers' => $attempt->answers->keyBy('question_id'),
            'remainingSeconds' => (int) $attempt->exam->duration_minutes > 0
                ? max(0, $attempt->started_at->copy()->addMinutes((int) $attempt->exam->duration_minutes)->diffInSeconds(now(), false) * -1)
                : null,
        ]);
    }

    public function submit(SubmitInternalExamRequest $request, ExamAttempt $attempt, StudentExamService $exams): RedirectResponse
    {
        $attempt = $exams->submit($request->user(), $attempt, $request->validated('answers', []));

        if ($attempt->status === 'expired') {
            return redirect()->route('student.exams.result', $attempt->exam_id)
                ->with('error', 'مهلت آزمون به پایان رسید؛ این تلاش منقضی شد و در تعداد دفعات مجاز محاسبه گردید.');
        }

        return redirect()->route('student.exams.result', $attempt->exam_id)
            ->with('success', $attempt->status === 'pending_review'
                ? 'پاسخ‌ها ثبت شد و برای تصحیح تشریحی به مدرس ارسال شد.'
                : 'پاسخ‌ها ثبت شد و نتیجه آزمون آماده است.');
    }

    public function result(Request $request, Exam $exam, StudentExamService $exams): View
    {
        abort_unless($exams->canAccess($request->user(), $exam), 404);

        $attempts = $exam->attempts()
            ->where('student_id', $request->user()->id)
            ->with('reviewer:id,name')
            ->latest('attempt_number')
            ->get();

        return view('student.exam-result', compact('exam', 'attempts'));
    }
}
