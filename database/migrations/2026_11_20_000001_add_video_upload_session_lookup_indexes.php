<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_upload_sessions', function (Blueprint $table): void {
            $table->index(
                ['target', 'target_id', 'status', 'expires_at'],
                'video_upload_sessions_target_state_expiry_index'
            );
            $table->index(
                ['status', 'expires_at'],
                'video_upload_sessions_state_expiry_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('video_upload_sessions', function (Blueprint $table): void {
            $table->dropIndex('video_upload_sessions_target_state_expiry_index');
            $table->dropIndex('video_upload_sessions_state_expiry_index');
        });
    }
};
