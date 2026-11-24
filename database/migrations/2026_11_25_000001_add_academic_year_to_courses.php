<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            // Start year of the Persian academic year (e.g. 1405 for 1405–1406).
            // Historical rows remain unclassified until an administrator verifies their year.
            $table->unsignedSmallInteger('academic_year')->nullable();
            $table->index(['academic_year', 'academic_grade_id', 'season', 'status'], 'courses_year_grade_season_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex('courses_year_grade_season_status_idx');
            $table->dropColumn('academic_year');
        });
    }
};
