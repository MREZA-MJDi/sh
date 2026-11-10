<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spot_player_settings', function (Blueprint $table) {
            $table->id();
            $table->string('account_id')->nullable();
            $table->text('api_key')->nullable();
            $table->unsignedTinyInteger('max_devices')->default(2);
            $table->boolean('watermark_enabled')->default(true);
            $table->boolean('auto_license_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('spot_player_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('external_id')->nullable();
            $table->text('embed_url')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('spot_player_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('external_license_id')->nullable()->unique();
            $table->unsignedTinyInteger('max_devices')->default(2);
            $table->unsignedTinyInteger('active_devices')->default(0);
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'course_id']);
        });

        Schema::create('quiz24_settings', function (Blueprint $table) {
            $table->id();
            $table->string('account_name')->nullable();
            $table->text('api_base_url')->nullable();
            $table->text('api_token')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz24_settings');
        Schema::dropIfExists('spot_player_licenses');
        Schema::dropIfExists('spot_player_videos');
        Schema::dropIfExists('spot_player_settings');
    }
};
