<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parent_meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('message')->nullable();
            $table->timestamp('preferred_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->string('status', 24)->default('requested');
            $table->timestamps();

            $table->index(['guardian_id', 'status', 'starts_at'], 'parent_meetings_guardian_status_start_idx');
            $table->index(['student_id', 'status', 'starts_at'], 'parent_meetings_student_status_start_idx');
            $table->index(['classroom_id', 'starts_at'], 'parent_meetings_classroom_start_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_meetings');
    }
};
