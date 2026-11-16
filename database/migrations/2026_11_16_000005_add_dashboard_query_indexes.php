<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->index(['student_id', 'session_date'], 'attendances_student_session_date_idx');
        });

        Schema::table('homework_submissions', function (Blueprint $table): void {
            $table->index(['student_id', 'status', 'submitted_at'], 'homework_submissions_student_status_submitted_idx');
        });

        Schema::table('exam_results', function (Blueprint $table): void {
            $table->index(['student_id', 'created_at'], 'exam_results_student_created_idx');
        });

        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->index(['classroom_id', 'status', 'release_at'], 'learning_resources_class_status_release_idx');
            $table->index(['course_id', 'status', 'release_at'], 'learning_resources_course_status_release_idx');
        });

        Schema::table('consultations', function (Blueprint $table): void {
            $table->index(['requester_id', 'created_at'], 'consultations_requester_created_idx');
            $table->index(['status', 'created_at'], 'consultations_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table): void {
            $table->dropIndex('consultations_requester_created_idx');
            $table->dropIndex('consultations_status_created_idx');
        });

        Schema::table('learning_resources', function (Blueprint $table): void {
            $table->dropIndex('learning_resources_class_status_release_idx');
            $table->dropIndex('learning_resources_course_status_release_idx');
        });

        Schema::table('exam_results', function (Blueprint $table): void {
            $table->dropIndex('exam_results_student_created_idx');
        });

        Schema::table('homework_submissions', function (Blueprint $table): void {
            $table->dropIndex('homework_submissions_student_status_submitted_idx');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex('attendances_student_session_date_idx');
        });
    }
};
