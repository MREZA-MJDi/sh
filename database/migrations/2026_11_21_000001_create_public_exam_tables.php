<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('public_exams', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subject')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('level')->default(2);
            $table->unsignedSmallInteger('duration_minutes')->default(10);
            $table->unsignedTinyInteger('pass_percent')->default(50);
            $table->boolean('negative_marking')->default(true);
            $table->boolean('shuffle')->default(true);
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('public_exam_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('public_exam_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->text('explanation')->nullable();
            $table->unsignedTinyInteger('points')->default(1);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->index(['public_exam_id', 'position']);
        });

        Schema::create('public_exam_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('public_exam_question_id')->constrained('public_exam_questions')->cascadeOnDelete();
            $table->string('body');
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('public_exam_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('public_exam_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('phone', 11);
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->timestamp('submitted_at')->nullable();
            $table->json('snapshot');
            $table->json('answers')->nullable();
            $table->unsignedSmallInteger('correct_count')->nullable();
            $table->unsignedSmallInteger('wrong_count')->nullable();
            $table->unsignedSmallInteger('blank_count')->nullable();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->unique(['public_exam_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_exam_attempts');
        Schema::dropIfExists('public_exam_options');
        Schema::dropIfExists('public_exam_questions');
        Schema::dropIfExists('public_exams');
    }
};
