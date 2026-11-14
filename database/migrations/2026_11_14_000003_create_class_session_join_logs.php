<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('class_session_join_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_session_id')->constrained('class_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('clicked_at');
            $table->timestamps();
            $table->index(['class_session_id', 'clicked_at']);
            $table->index(['user_id', 'clicked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_session_join_logs');
    }
};
