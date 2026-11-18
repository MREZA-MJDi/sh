<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_quiz_questions', function (Blueprint $table): void {
            $table->id();
            $table->string('category', 80);
            $table->string('type', 24)->default('multiple_choice');
            $table->text('question');
            $table->json('options')->nullable();
            $table->string('correct_answer', 255);
            $table->text('explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status', 16)->default('draft')->index();
            $table->timestamps();
            $table->index(['status', 'sort_order']);
        });

        $now = now();
        $defaults = [
            ['شیمی', 'در واکنش HCl + NaOH → NaCl + H₂O، این واکنش از چه نوعی است؟', ['A'=>'سوختن','B'=>'خنثی‌سازی اسید و باز','C'=>'تجزیه','D'=>'جانشینی یگانه'], 'B', 'اسید هیدروکلریک و سدیم هیدروکسید با هم واکنش می‌دهند و نمک و آب تولید می‌کنند؛ این واکنش نمونه‌ای از خنثی‌سازی است.'],
            ['زیست‌شناسی', 'کدام اندامک در بیشتر سلول‌های یوکاریوتی، محل اصلی تولید ATP در تنفس هوازی است؟', ['A'=>'ریبوزوم','B'=>'دستگاه گلژی','C'=>'میتوکندری','D'=>'شبکه آندوپلاسمی'], 'C', 'بخش عمده ATP حاصل از تنفس هوازی در میتوکندری و به‌ویژه طی زنجیره انتقال الکترون و فسفریلاسیون اکسایشی تولید می‌شود.'],
            ['ریاضی', 'اگر f(x) = x² باشد، مقدار مشتق تابع در x = ۳ چند است؟', ['A'=>'۶','B'=>'۳','C'=>'۹','D'=>'۱۲'], 'A', 'مشتق x² برابر ۲x است؛ با قرار دادن x = ۳، مقدار مشتق ۶ می‌شود.'],
            ['فیزیک', 'دو مقاومت ۲ و ۴ اهمی به‌صورت سری به هم وصل شده‌اند. مقاومت معادل چقدر است؟', ['A'=>'۲ اهم','B'=>'۴ اهم','C'=>'۶ اهم','D'=>'۸ اهم'], 'C', 'در اتصال سری، مقاومت‌ها با هم جمع می‌شوند؛ بنابراین مقاومت معادل ۶ اهم است.'],
            ['شیمی', 'اگر غلظت یون هیدروژن در یک محلول اسید قوی برابر ۱۰ به توان منفی ۳ مولار باشد، pH آن تقریباً چند است؟', ['A'=>'۱','B'=>'۲','C'=>'۴','D'=>'۳'], 'D', 'pH برابر منفی لگاریتم ده‌دهی غلظت یون هیدروژن است؛ پس مقدار pH برابر ۳ خواهد بود.'],
        ];

        foreach ($defaults as $index => [$category, $question, $options, $answer, $explanation]) {
            DB::table('home_quiz_questions')->insert([
                'category' => $category,
                'type' => 'multiple_choice',
                'question' => $question,
                'options' => json_encode($options, JSON_UNESCAPED_UNICODE),
                'correct_answer' => $answer,
                'explanation' => $explanation,
                'sort_order' => $index + 1,
                'status' => 'published',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('home_quiz_questions');
    }
};
