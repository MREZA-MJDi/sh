<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classroom_student', function (Blueprint $table): void {
            $table->timestamp('left_at')->nullable()->after('assigned_by');
            $table->index(['student_id', 'left_at'], 'classroom_student_active_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('classroom_student', function (Blueprint $table): void {
            $table->dropIndex('classroom_student_active_lookup');
            $table->dropColumn('left_at');
        });
    }
};
