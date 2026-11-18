<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->index(
                ['classroom_id', 'session_date', 'status'],
                'attendances_classroom_date_status_idx'
            );
        });

        Schema::table('homework_submissions', function (Blueprint $table): void {
            $table->index(
                ['status', 'homework_id', 'submitted_at'],
                'homework_submissions_status_homework_submitted_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table): void {
            $table->dropIndex('homework_submissions_status_homework_submitted_idx');
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex('attendances_classroom_date_status_idx');
        });
    }
};
