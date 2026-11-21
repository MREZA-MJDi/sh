<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PublicExam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class PublicExamController extends Controller
{
    public function index(): View
    {
        $exams = PublicExam::query()
            ->withCount(['questions', 'attempts'])
            ->with(['questions.options'])
            ->latest()
            ->paginate(15);

        return view('admin.public-exams.index', compact('exams'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $questions = $data['questions'] ?? [];
        unset($data['questions']);

        DB::transaction(function () use ($data, $questions): void {
            $exam = PublicExam::create([
                ...$data,
                'slug' => Str::slug($data['title']).'-'.Str::lower(Str::random(6)),
            ]);
            $this->syncQuestions($exam, $questions);
        });

        return redirect()->route('admin.public-exams.index')->with('success', 'آزمون عمومی ذخیره شد.');
    }

    public function update(Request $request, PublicExam $publicExam): RedirectResponse
    {
        $data = $this->validated($request);
        $questions = $data['questions'] ?? [];
        unset($data['questions']);

        if ($data['is_published'] && count($questions) === 0 && ! $publicExam->questions()->exists()) {
            throw ValidationException::withMessages(['questions' => 'برای انتشار، حداقل یک سؤال ثبت کنید.']);
        }

        DB::transaction(function () use ($publicExam, $data, $questions): void {
            $publicExam->update($data);
            $this->syncQuestions($publicExam, $questions);
        });

        return back()->with('success', 'آزمون عمومی به‌روزرسانی شد.');
    }

    public function destroy(PublicExam $publicExam): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('admin'), 403);
        if ($publicExam->attempts()->exists()) {
            return back()->withErrors(['exam' => 'این آزمون سابقه شرکت دارد و برای حفظ نتایج قابل حذف نیست.']);
        }

        $publicExam->delete();

        return back()->with('success', 'آزمون حذف شد.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'subject' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:3000'],
            'level' => ['required', 'integer', 'between:1,3'],
            'duration_minutes' => ['required', 'integer', 'between:1,240'],
            'pass_percent' => ['required', 'integer', 'between:0,100'],
            'negative_marking' => ['sometimes', 'boolean'],
            'shuffle' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'questions' => ['nullable', 'array', 'max:100'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.body' => ['required', 'string', 'max:5000'],
            'questions.*.explanation' => ['nullable', 'string', 'max:5000'],
            'questions.*.points' => ['required', 'integer', 'between:1,20'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.options.*.id' => ['nullable', 'integer'],
            'questions.*.options.*.body' => ['required', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['sometimes', 'boolean'],
        ]);

        foreach ($data['questions'] ?? [] as $index => $question) {
            if (collect($question['options'])->where('is_correct', true)->count() !== 1) {
                throw ValidationException::withMessages(["questions.$index.options" => 'برای هر سؤال دقیقاً یک گزینه صحیح انتخاب کنید.']);
            }
        }

        $data['negative_marking'] = $request->boolean('negative_marking');
        $data['shuffle'] = $request->boolean('shuffle');
        $data['is_published'] = $request->boolean('is_published');
        if ($data['is_published'] && count($data['questions'] ?? []) === 0) {
            throw ValidationException::withMessages(['questions' => 'برای انتشار، حداقل یک سؤال ثبت کنید.']);
        }

        return $data;
    }

    private function syncQuestions(PublicExam $exam, array $questions): void
    {
        $keptQuestions = [];

        foreach (array_values($questions) as $position => $data) {
            $question = null;
            if (! empty($data['id'])) {
                $question = $exam->questions()->whereKey($data['id'])->first();
                if (! $question) {
                    throw ValidationException::withMessages(["questions.$position.id" => 'سؤال انتخاب‌شده متعلق به این آزمون نیست.']);
                }
                $question->update(Arr::only($data, ['body', 'explanation', 'points']) + ['position' => $position]);
            } else {
                $question = $exam->questions()->create(Arr::only($data, ['body', 'explanation', 'points']) + ['position' => $position]);
            }

            $keptQuestions[] = $question->id;
            $keptOptions = [];

            foreach (array_values($data['options']) as $optionPosition => $optionData) {
                $option = null;
                if (! empty($optionData['id'])) {
                    $option = $question->options()->whereKey($optionData['id'])->first();
                    if (! $option) {
                        throw ValidationException::withMessages(["questions.$position.options.$optionPosition.id" => 'گزینه انتخاب‌شده متعلق به این سؤال نیست.']);
                    }
                    $option->update([
                        'body' => $optionData['body'],
                        'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                        'position' => $optionPosition,
                    ]);
                } else {
                    $option = $question->options()->create([
                        'body' => $optionData['body'],
                        'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                        'position' => $optionPosition,
                    ]);
                }
                $keptOptions[] = $option->id;
            }

            $question->options()->whereNotIn('id', $keptOptions)->delete();
        }

        $exam->questions()->whereNotIn('id', $keptQuestions)->delete();
    }
}
