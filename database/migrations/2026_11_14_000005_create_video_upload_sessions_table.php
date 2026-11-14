<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('video_upload_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('upload_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('target', 20);
            $table->unsignedBigInteger('target_id');
            $table->string('status', 20)->default('uploading');
            $table->unsignedBigInteger('bytes_uploaded')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_upload_sessions');
    }
};
