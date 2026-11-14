<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('video_upload_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('video_upload_sessions', 'metadata')) {
                $table->json('metadata')->nullable()->after('bytes_uploaded');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('video_upload_sessions', 'metadata')) {
            Schema::table('video_upload_sessions', function (Blueprint $table): void {
                $table->dropColumn('metadata');
            });
        }
    }
};