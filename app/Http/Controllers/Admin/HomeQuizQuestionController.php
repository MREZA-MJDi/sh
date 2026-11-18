<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeQuizQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

final class HomeQuizQuestionController extends Controller
{
    public function index(): View
    {
        return view('admin.seo.quiz', [
            'questions' => HomeQuizQuestion::query()->orderBy('sort_order')->orderBy('id')->paginate(20),
            'publishedCount' => HomeQuizQuestion::published()->count(),
            'draftCount' => HomeQuizQuestion::query()->where('status', 'draft')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        HomeQuizQuestion::query()->create($data);

        return back()->with('success', 'سؤال صفحه اصلی ثبت شد.');
    }

    public function update(Request $request, HomeQuizQuestion $question): RedirectResponse
    {
        $question->update($this->validatedData($request));

        return back()->with('success', 'سؤال صفحه اصلی به‌روزرسانی شد.');
    }

    public function destroy(HomeQuizQuestion $question): RedirectResponse
    {
        $question->delete();

        return back()->with('success', 'سؤال حذف شد.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(['multiple_choice', 'riddle'])],
            'question' => ['required', 'string', 'max:1200'],
            'option_a' => ['required_if:type,multiple_choice', 'nullable', 'string', 'max:255'],
            'option_b' => ['required_if:type,multiple_choice', 'nullable', 'string', 'max:255'],
            'option_c' => ['required_if:type,multiple_choice', 'nullable', 'string', 'max:255'],
            'option_d' => ['required_if:type,multiple_choice', 'nullable', 'string', 'max:255'],
            'correct_answer' => ['required', 'string', 'max:255'],
            'explanation' => ['nullable', 'string', 'max:1200'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::in(['published', 'draft'])],
        ]);

        if ($data['type'] === 'multiple_choice') {
            if (! in_array($data['correct_answer'], ['A', 'B', 'C', 'D'], true)) {
                validator([], [])->errors();
                abort(422, 'برای آزمون چهارگزینه‌ای، پاسخ صحیح باید A، B، C یا D باشد.');
            }

            $data['options'] = [
                'A' => $data['option_a'],
                'B' => $data['option_b'],
                'C' => $data['option_c'],
                'D' => $data['option_d'],
            ];
        } else {
            $data['options'] = null;
        }

        unset($data['option_a'], $data['option_b'], $data['option_c'], $data['option_d']);

        return $data;
    }
}
