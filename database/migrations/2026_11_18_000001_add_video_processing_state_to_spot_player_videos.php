<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_player_videos', function (Blueprint $table): void {
            if (! Schema::hasColumn('spot_player_videos', 'processing_error')) {
                $table->text('processing_error')->nullable();
            }

            if (! Schema::hasColumn('spot_player_videos', 'processed_at')) {
                $table->timestamp('processed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('spot_player_videos', function (Blueprint $table): void {
            $columns = array_filter([
                Schema::hasColumn('spot_player_videos', 'processing_error') ? 'processing_error' : null,
                Schema::hasColumn('spot_player_videos', 'processed_at') ? 'processed_at' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
