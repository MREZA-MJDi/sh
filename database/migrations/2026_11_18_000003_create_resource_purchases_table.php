<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('learning_resource_id')->constrained('learning_resources')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('reference', 40)->unique();
            $table->string('method', 30)->default('bank_transfer');
            $table->string('provider', 30)->default('manual');
            $table->string('provider_reference', 190)->nullable();
            $table->string('status', 30)->default('pending_review');
            $table->text('note')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['learning_resource_id', 'student_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_purchases');
    }
};
