<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->unsignedTinyInteger('attempts_allowed')->default(1);
        });

        Schema::create('questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('single');
            $table->longText('question');
            $table->json('options')->nullable();
            $table->longText('correct_answer')->nullable();
            $table->decimal('score', 8, 2)->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['exam_id', 'sort_order']);
        });

        Schema::create('exam_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number');
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->default(0);
            $table->string('status', 24)->default('in_progress')->index();
            $table->longText('feedback')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['exam_id', 'student_id', 'attempt_number']);
            $table->index(['student_id', 'status', 'submitted_at']);
        });

        Schema::table('exam_results', function (Blueprint $table): void {
            $table->longText('feedback')->nullable();
        });

        Schema::create('exam_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->longText('answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['exam_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_answers');
        Schema::table('exam_results', function (Blueprint $table): void {
            $table->dropColumn('feedback');
        });
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('questions');

        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn('attempts_allowed');
        });
    }
};
