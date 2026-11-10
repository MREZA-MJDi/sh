<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->string('session_type', 24)->default('online');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('meeting_provider', 40)->nullable();
            $table->text('meeting_url')->nullable();
            $table->string('status', 24)->default('scheduled');
            $table->text('teacher_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['classroom_id', 'starts_at'], 'class_sessions_classroom_start_idx');
            $table->index(['status', 'starts_at'], 'class_sessions_status_start_idx');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->foreignId('class_session_id')
                ->nullable()
                ->after('classroom_id')
                ->constrained('class_sessions')
                ->nullOnDelete();
            $table->index(['class_session_id', 'status'], 'attendance_session_status_idx');
        });

        // Preserve historical attendance by grouping each existing classroom/date
        // into one explicit session before linking attendance rows.
        $groups = DB::table('attendances')
            ->select('classroom_id', 'session_date')
            ->whereNull('class_session_id')
            ->groupBy('classroom_id', 'session_date')
            ->orderBy('classroom_id')
            ->orderBy('session_date')
            ->get();

        foreach ($groups as $group) {
            $sessionId = DB::table('class_sessions')->insertGetId([
                'classroom_id' => $group->classroom_id,
                'title' => 'جلسه ثبت‌شده ' . $group->session_date,
                'session_type' => 'online',
                'starts_at' => $group->session_date . ' 00:00:00',
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('attendances')
                ->where('classroom_id', $group->classroom_id)
                ->whereDate('session_date', $group->session_date)
                ->update(['class_session_id' => $sessionId]);
        }

        Schema::table('attendances', function (Blueprint $table): void {
            $table->unique(['class_session_id', 'student_id'], 'attendance_session_student_unique');
        });

        Schema::table('classrooms', function (Blueprint $table): void {
            $table->index(['teacher_id', 'status'], 'classrooms_teacher_status_idx');
            $table->index(['course_id', 'status'], 'classrooms_course_status_idx');
        });

        Schema::table('homeworks', function (Blueprint $table): void {
            $table->index(['classroom_id', 'status', 'due_at'], 'homeworks_classroom_status_due_idx');
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->index(['classroom_id', 'status', 'starts_at'], 'exams_classroom_status_start_idx');
            $table->index(['course_id', 'status', 'starts_at'], 'exams_course_status_start_idx');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->index(['recipient_id', 'status', 'created_at'], 'messages_recipient_status_created_idx');
            $table->index(['sender_id', 'created_at'], 'messages_sender_created_idx');
        });

        Schema::table('course_enrollments', function (Blueprint $table): void {
            $table->index(['course_id', 'status', 'expires_at'], 'course_enrollments_course_status_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::table('course_enrollments', function (Blueprint $table): void {
            $table->dropIndex('course_enrollments_course_status_expiry_idx');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_recipient_status_created_idx');
            $table->dropIndex('messages_sender_created_idx');
        });

        Schema::table('exams', function (Blueprint $table): void {
            $table->dropIndex('exams_classroom_status_start_idx');
            $table->dropIndex('exams_course_status_start_idx');
        });

        Schema::table('homeworks', function (Blueprint $table): void {
            $table->dropIndex('homeworks_classroom_status_due_idx');
        });

        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropIndex('classrooms_teacher_status_idx');
            $table->dropIndex('classrooms_course_status_idx');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropUnique('attendance_session_student_unique');
            $table->dropIndex('attendance_session_status_idx');
            $table->dropConstrainedForeignId('class_session_id');
        });

        Schema::dropIfExists('class_sessions');
    }
};
