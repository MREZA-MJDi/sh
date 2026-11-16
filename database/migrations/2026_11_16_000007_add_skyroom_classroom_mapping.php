<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->string('skyroom_room_id', 120)->nullable()->unique();
            $table->string('skyroom_status', 32)->default('not_requested');
            $table->string('skyroom_note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropUnique(['skyroom_room_id']);
            $table->dropColumn(['skyroom_room_id', 'skyroom_status', 'skyroom_note']);
        });
    }
};
