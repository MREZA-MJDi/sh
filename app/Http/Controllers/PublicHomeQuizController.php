<?php

namespace App\Http\Controllers;

use App\Models\HomeQuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHomeQuizController extends Controller
{
    public function question(int $step): JsonResponse
    {
        $questions = HomeQuizQuestion::published()->get();
        $question = $questions->get($step - 1);

        if (! $question) {
            return response()->json(['message' => 'مرحله موردنظر وجود ندارد.'], 404);
        }

        return response()->json([
            'success' => true,
            'step' => $step,
            'total' => $questions->count(),
            'question' => [
                'id' => 'q'.$step,
                'category' => $question->category,
                'type' => $question->type,
                'question' => $question->question,
                'options' => $question->type === 'multiple_choice' ? $question->options : null,
            ],
        ]);
    }

    public function answer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'step' => ['required', 'integer', 'min:1', function ($attribute, $value, $fail): void {\n                if ((int) $value > HomeQuizQuestion::published()->count()) {\n                    $fail('مرحله موردنظر وجود ندارد.');\n                }\n            }],
            'answer' => ['required', 'string', 'max:255'],
        ]);

        $questions = HomeQuizQuestion::published()->get();
        $question = $questions->get(((int) $validated['step']) - 1);

        if (! $question) {
            return response()->json(['message' => 'مرحله موردنظر وجود ندارد.'], 404);
        }

        if ($question->type === 'multiple_choice') {
            if (! in_array($validated['answer'], ['A', 'B', 'C', 'D'], true)) {
                return response()->json(['message' => 'گزینه انتخاب‌شده معتبر نیست.'], 422);
            }
            $isCorrect = $validated['answer'] === $question->correct_answer;
            $correctLabel = data_get($question->options, $question->correct_answer, $question->correct_answer);
        } else {
            $normalize = fn (string $value): string => mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
            $isCorrect = $normalize($validated['answer']) === $normalize($question->correct_answer);
            $correctLabel = $question->correct_answer;
        }

        return response()->json([
            'success' => true,
            'correct' => $isCorrect,
            'selected' => $validated['answer'],
            'correct_answer' => $question->correct_answer,
            'correct_label' => $correctLabel,
            'explanation' => $question->explanation,
            'is_last' => (int) $validated['step'] === $questions->count(),
            'step' => (int) $validated['step'],
            'total' => $questions->count(),
        ]);
    }
}
