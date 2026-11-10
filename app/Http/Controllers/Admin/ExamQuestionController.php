<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Services\Admin\ExamWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ExamQuestionController extends Controller
{
    public function index(Exam $exam, ExamWorkspaceService $workspace): View
    {
        abort_unless($workspace->owns(auth()->user(), $exam), 404);
        $exam->load(['questions' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')]);

        return view('admin.exams.questions', compact('exam'));
    }

    public function store(Request $request, Exam $exam, ExamWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($workspace->owns($request->user(), $exam), 404);
        abort_if($exam->provider !== 'internal', 422, 'سؤال‌های آزمون خارجی باید در سرویس ارائه‌دهنده مدیریت شوند.');

        $exam->questions()->create($this->validatedQuestion($request));

        return back()->with('success', 'سؤال به آزمون اضافه شد.');
    }

    public function update(Request $request, Exam $exam, Question $question, ExamWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($workspace->owns($request->user(), $exam), 404);
        abort_unless((int) $question->exam_id === (int) $exam->id, 404);
        abort_if($exam->provider !== 'internal', 422);

        $question->update($this->validatedQuestion($request));

        return back()->with('success', 'سؤال به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Exam $exam, Question $question, ExamWorkspaceService $workspace): RedirectResponse
    {
        abort_unless($workspace->owns($request->user(), $exam), 404);
        abort_unless((int) $question->exam_id === (int) $exam->id, 404);

        if ($question->answers()->exists()) {
            throw ValidationException::withMessages([
                'question' => 'برای این سؤال پاسخ ثبت شده است؛ حذف مجاز نیست و سابقه آزمون باید حفظ شود.',
            ]);
        }

        $question->delete();

        return back()->with('success', 'سؤال حذف شد.');
    }

    private function validatedQuestion(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:single,multiple,text'],
            'question' => ['required', 'string', 'max:10000'],
            'options_text' => ['nullable', 'string', 'max:12000'],
            'correct_answer' => ['nullable', 'string', 'max:2000'],
            'score' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ]);

        $options = collect(preg_split('/\R/u', (string) ($data['options_text'] ?? '')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values();

        if ($data['type'] !== 'text' && $options->count() < 2) {
            throw ValidationException::withMessages(['options_text' => 'برای سؤال تستی حداقل دو گزینه وارد کنید.']);
        }

        $correctAnswer = null;
        if ($data['type'] === 'single') {
            if (! $options->contains($data['correct_answer'])) {
                throw ValidationException::withMessages(['correct_answer' => 'پاسخ صحیح باید دقیقاً یکی از گزینه‌ها باشد.']);
            }
            $correctAnswer = $data['correct_answer'];
        }

        if ($data['type'] === 'multiple') {
            $correctItems = collect(explode(',', (string) $data['correct_answer']))
                ->map(fn ($value) => trim($value))->filter()->unique()->values();

            if ($correctItems->isEmpty() || $correctItems->diff($options)->isNotEmpty()) {
                throw ValidationException::withMessages(['correct_answer' => 'پاسخ‌های صحیح باید از گزینه‌های ثبت‌شده انتخاب شوند.']);
            }

            $correctAnswer = $correctItems->toJson(JSON_UNESCAPED_UNICODE);
        }

        return [
            'type' => $data['type'],
            'question' => $data['question'],
            'options' => $data['type'] === 'text' ? null : $options->all(),
            'correct_answer' => $correctAnswer,
            'score' => $data['score'],
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }
}
