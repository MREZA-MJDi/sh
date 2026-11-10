<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_membership_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('joined_at');
            $table->timestamp('left_at');
            $table->timestamps();

            $table->index(['classroom_id', 'student_id', 'left_at'], 'classroom_membership_history_lookup_idx');
            $table->index(['student_id', 'left_at'], 'classroom_membership_history_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_membership_history');
    }
};
