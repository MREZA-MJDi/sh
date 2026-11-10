<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz24_student_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->default('quiz24');
            $table->string('external_identifier', 190);
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['provider', 'external_identifier'], 'quiz24_student_mapping_unique');
            $table->index(['student_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz24_student_mappings');
    }
};
