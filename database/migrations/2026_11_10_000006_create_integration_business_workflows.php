<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('integration_status', 32)->default('not_required')->index();
            $table->text('integration_note')->nullable();
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->string('integration_status', 32)->default('not_required')->index();
            $table->text('integration_note')->nullable();
        });

        Schema::table('integration_runs', function (Blueprint $table) {
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable()->index();
            $table->uuid('correlation_id')->nullable()->index();
        });

        Schema::table('spot_player_settings', function (Blueprint $table) {
            $table->boolean('api_enabled')->default(false);
            $table->string('contract_version', 50)->nullable();
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('source', 30)->default('classroom');
            $table->string('status', 20)->default('active')->index();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_reference', 190)->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'student_id']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');

        Schema::table('spot_player_settings', function (Blueprint $table) {
            $table->dropColumn(['api_enabled', 'contract_version']);
        });

        Schema::table('integration_runs', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['available_at']);
            $table->dropIndex(['correlation_id']);
            $table->dropColumn(['idempotency_key', 'attempts', 'available_at', 'correlation_id']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex(['integration_status']);
            $table->dropColumn(['integration_status', 'integration_note']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['integration_status']);
            $table->dropColumn(['integration_status', 'integration_note']);
        });
    }
};
