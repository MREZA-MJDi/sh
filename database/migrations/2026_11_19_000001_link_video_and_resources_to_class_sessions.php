<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('spot_player_videos') && ! Schema::hasColumn('spot_player_videos', 'class_session_id')) {
            Schema::table('spot_player_videos', function (Blueprint $table): void {
                $table->foreignId('class_session_id')
                    ->nullable()
                    ->after('classroom_id')
                    ->constrained('class_sessions')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('learning_resources') && ! Schema::hasColumn('learning_resources', 'class_session_id')) {
            Schema::table('learning_resources', function (Blueprint $table): void {
                $table->foreignId('class_session_id')
                    ->nullable()
                    ->after('classroom_id')
                    ->constrained('class_sessions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('learning_resources') && Schema::hasColumn('learning_resources', 'class_session_id')) {
            Schema::table('learning_resources', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('class_session_id');
            });
        }

        if (Schema::hasTable('spot_player_videos') && Schema::hasColumn('spot_player_videos', 'class_session_id')) {
            Schema::table('spot_player_videos', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('class_session_id');
            });
        }
    }
};
