<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120)->nullable();
            $table->text('body');
            $table->string('tone', 12)->default('');
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
        });

        Schema::create('homework_submission_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('homework_submission_id')->constrained('homework_submissions')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index('homework_submission_id');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->foreignId('student_id')->nullable()->after('recipient_id')->constrained('users')->nullOnDelete();
            $table->index(['student_id', 'created_at']);
        });

        Schema::table('consultations', function (Blueprint $table): void {
            $table->string('topic', 190)->nullable()->after('type');
            $table->string('contact_phone', 30)->nullable()->after('topic');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            $table->dropColumn(['topic', 'contact_phone']);
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('student_id');
        });

        Schema::dropIfExists('homework_submission_files');
        Schema::dropIfExists('student_notes');
    }
};
