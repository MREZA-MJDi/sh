<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('student_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('method', 30)->default('gateway');
            $table->string('provider', 30)->default('pending');
            $table->string('status', 30)->default('awaiting_provider')->index();
            $table->string('provider_reference', 190)->nullable()->index();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('payments'); }
};
