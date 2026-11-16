
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicHomeQuizController extends Controller
{
    private function questions(): array
    {
        return [
            1 => [
                'id' => 'q1',
                'category' => 'ریاضی',
                'question' => 'اگر ۳x + ۵ = ۲۰ باشد، مقدار x چند است؟',
                'options' => [
                    'A' => '۳',
                    'B' => '۵',
                    'C' => '۷',
                    'D' => '۸',
                ],
                'answer' => 'B',
                'explanation' => 'ابتدا ۵ را از دو طرف کم می‌کنیم: ۳x = ۱۵. سپس دو طرف را بر ۳ تقسیم می‌کنیم؛ بنابراین x برابر ۵ است.',
            ],

            2 => [
                'id' => 'q2',
                'category' => 'علوم',
                'question' => 'کدام سیاره در منظومه شمسی به داشتن حلقه‌های بزرگ و مشخص معروف است؟',
                'options' => [
                    'A' => 'مریخ',
                    'B' => 'زهره',
                    'C' => 'زحل',
                    'D' => 'عطارد',
                ],
                'answer' => 'C',
                'explanation' => 'زحل به خاطر حلقه‌های گسترده و قابل مشاهده‌اش شناخته می‌شود. این حلقه‌ها عمدتاً از ذرات یخ و سنگ تشکیل شده‌اند.',
            ],

            3 => [
                'id' => 'q3',
                'category' => 'فیزیک',
                'question' => 'اگر سرعت یک جسم در مدت ۲ ثانیه از صفر به ۱۰ متر بر ثانیه برسد، شتاب متوسط آن چقدر است؟',
                'options' => [
                    'A' => '۲ متر بر مجذور ثانیه',
                    'B' => '۵ متر بر مجذور ثانیه',
                    'C' => '۱۰ متر بر مجذور ثانیه',
                    'D' => '۲۰ متر بر مجذور ثانیه',
                ],
                'answer' => 'B',
                'explanation' => 'شتاب متوسط برابر تغییر سرعت تقسیم بر زمان است: (۱۰ منهای صفر) تقسیم بر ۲، یعنی ۵ متر بر مجذور ثانیه.',
            ],

            4 => [
                'id' => 'q4',
                'category' => 'منطق',
                'question' => 'همه دانش‌آموزان یک کلاس در آزمون شرکت کرده‌اند. علی دانش‌آموز آن کلاس است. کدام نتیجه حتماً درست است؟',
                'options' => [
                    'A' => 'علی حتماً نمره کامل گرفته است.',
                    'B' => 'علی در آزمون شرکت کرده است.',
                    'C' => 'علی بهترین دانش‌آموز کلاس است.',
                    'D' => 'علی در همه درس‌ها موفق است.',
                ],
                'answer' => 'B',
                'explanation' => 'طبق فرض سؤال تمام دانش‌آموزان کلاس در آزمون شرکت کرده‌اند و علی نیز دانش‌آموز همان کلاس است؛ پس شرکت کردن او نتیجه قطعی است.',
            ],

            5 => [
                'id' => 'q5',
                'category' => 'ریاضی',
                'question' => 'محیط یک مربع با ضلع ۶ سانتی‌متر چند سانتی‌متر است؟',
                'options' => [
                    'A' => '۱۲',
                    'B' => '۱۸',
                    'C' => '۲۴',
                    'D' => '۳۶',
                ],
                'answer' => 'C',
                'explanation' => 'مربع چهار ضلع برابر دارد. محیط آن برابر ۴ ضربدر طول ضلع است؛ یعنی ۴ × ۶ = ۲۴ سانتی‌متر.',
            ],
        ];
    }

    /**
     * نمایش سؤال موردنظر
     */
    public function question(int $step): JsonResponse
    {
        $questions = $this->questions();

        if (!isset($questions[$step])) {
            return response()->json([
                'message' => 'مرحله موردنظر وجود ندارد.',
            ], 404);
        }

        $question = $questions[$step];

        // پاسخ صحیح هرگز در خروجی سؤال ارسال نمی‌شود.
        unset($question['answer'], $question['explanation']);

        return response()->json([
            'success' => true,
            'step' => $step,
            'total' => count($questions),
            'question' => $question,
        ]);
    }

    /**
     * بررسی پاسخ کاربر
     */
    public function answer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'step' => ['required', 'integer', 'between:1,5'],
            'answer' => ['required', 'string', 'in:A,B,C,D'],
        ]);

        $questions = $this->questions();

        $step = (int) $validated['step'];
        $question = $questions[$step];

        $isCorrect = $validated['answer'] === $question['answer'];

        return response()->json([
            'success' => true,
            'correct' => $isCorrect,
            'selected' => $validated['answer'],
            'correct_answer' => $question['answer'],
            'correct_label' => $question['options'][$question['answer']],
            'explanation' => $question['explanation'],
            'is_last' => $step === count($questions),
            'step' => $step,
            'total' => count($questions),
        ]);
    }
}
