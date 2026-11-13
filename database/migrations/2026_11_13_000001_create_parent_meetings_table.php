<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parent_meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('agenda')->nullable();
            $table->string('meeting_type', 24)->default('in_person');
            $table->timestamp('proposed_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 24)->default('proposed');
            $table->text('admin_notes')->nullable();
            $table->timestamp('guardian_responded_at')->nullable();
            $table->timestamps();
            $table->index(['guardian_id', 'status', 'scheduled_at'], 'parent_meetings_guardian_status_idx');
            $table->index(['student_id', 'scheduled_at'], 'parent_meetings_student_schedule_idx');
            $table->index(['status', 'scheduled_at'], 'parent_meetings_status_schedule_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_meetings');
    }
};