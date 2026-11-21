<?php

namespace Database\Seeders;

use App\Models\PublicExam;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PublicExamSeeder extends Seeder
{
    public function run(): void
    {
        $exam = PublicExam::withTrashed()->firstOrNew(['slug' => 'logical-reasoning-free']);
        if ($exam->trashed()) {
            $exam->restore();
        }

        $exam->fill([
            'title' => 'آزمون رایگان هوش و استدلال منطقی',
            'subject' => 'استدلال',
            'level' => 2,
            'duration_minutes' => 10,
            'pass_percent' => 50,
            'negative_marking' => true,
            'shuffle' => true,
            // Seed content is deliberately not published. Global public access is also disabled by default.
            'is_published' => false,
            'description' => 'شش سؤال کوتاه برای سنجش الگویابی، قیاس و استدلال. بعد از پایان، پاسخ تشریحی همه‌ی سؤال‌ها را می‌بینی.',
        ]);
        $exam->slug = 'logical-reasoning-free';
        $exam->save();

        $bank = [
            ['در دنباله‌ی ۲، ۶، ۱۲، ۲۰، ۳۰، … عدد بعدی کدام است؟',
                'اختلاف جمله‌ها ۴، ۶، ۸، ۱۰ است و اختلاف بعدی ۱۲ می‌شود؛ ۳۰ + ۱۲ = ۴۲.',
                ['۴۰', '۴۲', '۴۴', '۳۶'], 1],
            ['همه‌ی دبیران مطالعه می‌کنند و برخی مطالعه‌کنندگان ورزشکارند. کدام نتیجه الزاماً درست است؟',
                'آن «برخی ورزشکاران» ممکن است کاملاً بیرون از گروه دبیران باشند، پس هیچ‌کدام از سه نتیجه‌ی اول الزاماً درست نیست.',
                ['همه‌ی دبیران ورزشکارند', 'برخی دبیران ورزشکارند', 'هیچ دبیری ورزشکار نیست', 'هیچ‌کدام الزاماً درست نیست'], 3],
            ['اگر ۵ دستگاه در ۵ دقیقه ۵ قطعه تولید کنند، ۱۰۰ دستگاه برای تولید ۱۰۰ قطعه چند دقیقه زمان لازم دارند؟',
                'هر دستگاه در ۵ دقیقه یک قطعه می‌سازد. پس ۱۰۰ دستگاه هم همان ۵ دقیقه، ۱۰۰ قطعه می‌سازند.',
                ['۱۰۰', '۵', '۱', '۲۰'], 1],
            ['علی ۲ سال از سارا بزرگ‌تر است و مجموع سن آن‌ها ۳۰ سال است. سن علی چند سال است؟',
                'اگر سن سارا x باشد: x + (x + 2) = 30، پس x = 14 و سن علی ۱۶ است.',
                ['۱۴', '۱۵', '۱۶', '۱۸'], 2],
            ['کتاب به کتابخانه همان نسبتی را دارد که دارو به … دارد.',
                'کتابخانه جای نگهداری و عرضه‌ی کتاب است؛ جای نگهداری و عرضه‌ی دارو داروخانه است.',
                ['پزشک', 'داروخانه', 'بیمار', 'بیمارستان'], 1],
            ['در کیسه‌ای ۳ مهره‌ی قرمز و ۲ مهره‌ی آبی هست. حداقل چند مهره را بدون نگاه برداریم تا مطمئن شویم دست‌کم یک مهره‌ی قرمز داریم؟',
                'بدترین حالت این است که اول هر ۲ مهره‌ی آبی بیاید؛ مهره‌ی سوم حتماً قرمز است.',
                ['۱', '۲', '۳', '۴'], 2],
        ];

        foreach ($bank as $position => [$body, $explanation, $options, $correctIndex]) {
            $question = $exam->questions()->updateOrCreate(
                ['position' => $position],
                ['body' => $body, 'explanation' => $explanation, 'points' => 1]
            );

            foreach ($options as $optionPosition => $optionBody) {
                $question->options()->updateOrCreate(
                    ['position' => $optionPosition],
                    ['body' => $optionBody, 'is_correct' => $optionPosition === $correctIndex]
                );
            }
            $question->options()->where('position', '>=', count($options))->delete();
        }

        $exam->questions()->where('position', '>=', count($bank))->delete();
    }
}
