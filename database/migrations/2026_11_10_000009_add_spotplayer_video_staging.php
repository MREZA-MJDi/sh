<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_player_videos', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable();
            $table->string('file_original_name', 255)->nullable();
            $table->string('file_mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->char('file_sha256', 64)->nullable()->index();
            $table->timestamp('staged_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('spot_player_videos', function (Blueprint $table) {
            $table->dropIndex(['file_sha256']);
            $table->dropColumn(['file_path', 'file_original_name', 'file_mime_type', 'file_size_bytes', 'file_sha256', 'staged_at']);
        });
    }
};
