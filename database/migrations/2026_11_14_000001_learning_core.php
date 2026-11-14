<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ستون‌های دوره (فقط اگر وجود ندارند)
        $course = function (string $col, callable $def) {
            if (! Schema::hasColumn('courses', $col)) {
                Schema::table('courses', fn (Blueprint $t) => $def($t));
            }
        };
        $course('access_type', fn ($t) => $t->string('access_type', 10)->default('paid'));          // free | paid
        $course('price', fn ($t) => $t->unsignedBigInteger('price')->default(0));                   // واحد را با درگاه یکی کن
        $course('is_published', fn ($t) => $t->boolean('is_published')->default(false));
        $course('external_course_id', fn ($t) => $t->string('external_course_id')->nullable());     // شناسه دوره در SpotPlayer
        $course('teaser_original_path', fn ($t) => $t->string('teaser_original_path')->nullable()); // فایل خام (خصوصی)
        $course('teaser_path', fn ($t) => $t->string('teaser_path')->nullable());                   // خروجی فشرده (عمومی)
        $course('teaser_poster_path', fn ($t) => $t->string('teaser_poster_path')->nullable());
        $course('teaser_status', fn ($t) => $t->string('teaser_status', 20)->default('none'));      // none|processing|ready|failed
        $course('teaser_error', fn ($t) => $t->text('teaser_error')->nullable());

        // این شاخه هنوز جدول course_lessons را در schema اصلی ندارد؛ به جدول lessons دست نمی‌زنیم.
        if (Schema::hasTable('course_lessons')) {
            $lesson = function (string $col, callable $def) {
                if (! Schema::hasColumn('course_lessons', $col)) {
                    Schema::table('course_lessons', fn (Blueprint $t) => $def($t));
                }
            };
            $lesson('video_status', fn ($t) => $t->string('video_status', 20)->default('none'));
            $lesson('video_error', fn ($t) => $t->text('video_error')->nullable());
            $lesson('video_upload_path', fn ($t) => $t->string('video_upload_path')->nullable());
        }

        if (! Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('course_id')->constrained()->cascadeOnDelete();
                $t->string('status', 20)->default('pending_payment'); // pending_payment|active|cancelled|refunded
                $t->string('source', 10)->default('payment');         // free|payment|manual
                $t->unsignedBigInteger('amount')->default(0);
                $t->string('payment_ref')->nullable()->unique();      // Authority درگاه
                $t->timestamp('paid_at')->nullable();
                $t->timestamps();
                $t->unique(['user_id', 'course_id']);
                $t->index('status');
            });
        }

        if (! Schema::hasTable('course_licenses')) {
            Schema::create('course_licenses', function (Blueprint $t) {
                $t->id();
                $t->foreignId('enrollment_id')->unique()->constrained()->cascadeOnDelete();
                $t->string('status', 20)->default('queued'); // queued|waiting_contract|active|failed|revoked
                $t->string('external_license_id')->nullable();
                $t->text('license_key')->nullable();         // رمزنگاری‌شده با cast
                $t->unsignedTinyInteger('attempts')->default(0);
                $t->text('last_error')->nullable();
                $t->timestamp('issued_at')->nullable();
                $t->timestamps();
                $t->index('status');
            });
        }

        if (Schema::hasTable('course_lessons') && ! Schema::hasTable('lesson_progress')) {
            Schema::create('lesson_progress', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->foreignId('course_lesson_id')->constrained('course_lessons')->cascadeOnDelete();
                $t->timestamp('completed_at')->nullable();
                $t->unsignedInteger('last_position_seconds')->default(0);
                $t->timestamps();
                $t->unique(['user_id', 'course_lesson_id']);
            });
        }
    }

    public function down(): void
    {
        // Do not drop the legacy lesson_progress table (lesson_id/student_id).
        if (Schema::hasTable('lesson_progress') && Schema::hasColumn('lesson_progress', 'course_lesson_id') && Schema::hasColumn('lesson_progress', 'user_id')) {
            Schema::dropIfExists('lesson_progress');
        }
        if (Schema::hasTable('course_licenses') && Schema::hasColumn('course_licenses', 'enrollment_id') && Schema::hasColumn('course_licenses', 'license_key')) {
            Schema::dropIfExists('course_licenses');
        }
        // enrollments عمداً حذف نمی‌شود (ممکن است داده واقعی داشته باشد)
    }
};
