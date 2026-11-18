<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missing = [];
        if (! Schema::hasColumn('spot_player_videos', 'processing_error')) {
            $missing[] = 'processing_error';
        }
        if (! Schema::hasColumn('spot_player_videos', 'processed_at')) {
            $missing[] = 'processed_at';
        }

        if ($missing === []) {
            return;
        }

        Schema::table('spot_player_videos', function (Blueprint $table) use ($missing): void {
            if (in_array('processing_error', $missing, true)) {
                $table->text('processing_error')->nullable();
            }
            if (in_array('processed_at', $missing, true)) {
                $table->timestamp('processed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        $existing = array_values(array_filter([
            Schema::hasColumn('spot_player_videos', 'processing_error') ? 'processing_error' : null,
            Schema::hasColumn('spot_player_videos', 'processed_at') ? 'processed_at' : null,
        ]));

        if ($existing === []) {
            return;
        }

        Schema::table('spot_player_videos', function (Blueprint $table) use ($existing): void {
            $table->dropColumn($existing);
        });
    }
};
