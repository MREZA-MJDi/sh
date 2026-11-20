<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->string('video_provider', 24)->default('local')->after('status');
            $table->foreignId('media_asset_id')->nullable()->after('video_provider')->constrained('media_assets')->nullOnDelete();
            $table->foreignId('spot_player_video_id')->nullable()->after('media_asset_id')->constrained('spot_player_videos')->nullOnDelete();
            $table->boolean('is_free_preview')->default(false)->after('spot_player_video_id');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('media_asset_id');
            $table->dropConstrainedForeignId('spot_player_video_id');
            $table->dropColumn(['video_provider', 'is_free_preview']);
        });
    }
};
