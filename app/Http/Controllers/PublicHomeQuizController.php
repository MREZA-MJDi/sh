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
                'category' => 'شیمی',
                'question' => 'در واکنش HCl + NaOH → NaCl + H₂O، این واکنش از چه نوعی است؟',
                'options' => [
                    'A' => 'سوختن',
                    'B' => 'خنثی‌سازی اسید و باز',
                    'C' => 'تجزیه',
                    'D' => 'جانشینی یگانه',
                ],
                'answer' => 'B',
                'explanation' => 'اسید هیدروکلریک و سدیم هیدروکسید با هم واکنش می‌دهند و نمک و آب تولید می‌کنند؛ این واکنش نمونه‌ای از خنثی‌سازی است.',
            ],
            2 => [
                'id' => 'q2',
                'category' => 'زیست‌شناسی',
                'question' => 'کدام اندامک در بیشتر سلول‌های یوکاریوتی، محل اصلی تولید ATP در تنفس هوازی است؟',
                'options' => [
                    'A' => 'ریبوزوم',
                    'B' => 'دستگاه گلژی',
                    'C' => 'میتوکندری',
                    'D' => 'شبکه آندوپلاسمی',
                ],
                'answer' => 'C',
                'explanation' => 'بخش عمده ATP حاصل از تنفس هوازی در میتوکندری و به‌ویژه طی زنجیره انتقال الکترون و فسفریلاسیون اکسایشی تولید می‌شود.',
            ],
            3 => [
                'id' => 'q3',
                'category' => 'ریاضی',
                'question' => 'اگر f(x) = x² باشد، مقدار مشتق تابع در x = ۳ چند است؟',
                'options' => [
                    'A' => '۶',
                    'B' => '۳',
                    'C' => '۹',
                    'D' => '۱۲',
                ],
                'answer' => 'A',
                'explanation' => 'مشتق x² برابر ۲x است؛ با قرار دادن x = ۳، مقدار مشتق ۲ × ۳ = ۶ می‌شود.',
            ],
            4 => [
                'id' => 'q4',
                'category' => 'فیزیک',
                'question' => 'دو مقاومت ۲ و ۴ اهمی به‌صورت سری به هم وصل شده‌اند. مقاومت معادل چقدر است؟',
                'options' => [
                    'A' => '۲ اهم',
                    'B' => '۴ اهم',
                    'C' => '۶ اهم',
                    'D' => '۸ اهم',
                ],
                'answer' => 'C',
                'explanation' => 'در اتصال سری، مقاومت‌ها با هم جمع می‌شوند؛ بنابراین مقاومت معادل برابر ۲ + ۴ = ۶ اهم است.',
            ],
            5 => [
                'id' => 'q5',
                'category' => 'شیمی',
                'question' => 'اگر غلظت یون هیدروژن در یک محلول اسید قوی برابر ۱۰ به توان منفی ۳ مولار باشد، pH آن تقریباً چند است؟',
                'options' => [
                    'A' => '۱',
                    'B' => '۲',
                    'C' => '۴',
                    'D' => '۳',
                ],
                'answer' => 'D',
                'explanation' => 'pH برابر منفی لگاریتم ده‌دهی غلظت یون هیدروژن است؛ پس برای غلظت ۱۰⁻³ مولار، مقدار pH برابر ۳ خواهد بود.',
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
