<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->unsignedSmallInteger('duration_minutes')->default(120)->after('starts_at');
            $table->boolean('show_to_guardians')->default(true)->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table): void {
            $table->dropColumn(['duration_minutes', 'show_to_guardians']);
        });
    }
};
