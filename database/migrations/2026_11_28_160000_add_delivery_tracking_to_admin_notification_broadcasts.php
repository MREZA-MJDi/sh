<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notification_broadcasts', function (Blueprint $table): void {
            $table->string('delivery_status', 16)->default('sent');
            $table->unsignedBigInteger('recipient_max_id')->nullable();
            $table->unsignedBigInteger('recipient_cursor')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('admin_notification_broadcasts', function (Blueprint $table): void {
            $table->dropColumn(['delivery_status', 'recipient_max_id', 'recipient_cursor', 'delivered_count']);
        });
    }
};
