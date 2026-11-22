<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_notification_broadcasts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience', 24);
            $table->string('title', 180);
            $table->text('message');
            $table->string('url', 2048)->nullable();
            $table->string('action_label', 80)->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamps();

            $table->index(['audience', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_broadcasts');
    }
};
