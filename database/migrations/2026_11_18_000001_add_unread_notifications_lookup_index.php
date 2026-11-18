<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'notifications_unread_lookup_idx';

    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
                self::INDEX
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex(self::INDEX);
        });
    }
};
