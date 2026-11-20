<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spot_player_videos', function (Blueprint $table): void {
            // Existing recordings remain approved; new teacher submissions opt into review.
            $table->string('approval_status', 24)->default('approved')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('spot_player_videos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['approval_status', 'reviewed_at', 'review_note']);
        });
    }
};
