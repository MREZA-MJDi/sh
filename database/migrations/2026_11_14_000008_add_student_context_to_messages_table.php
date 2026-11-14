<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('messages', 'student_id')) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->foreignId('student_id')->nullable()->after('recipient_id')->constrained('users')->nullOnDelete();
                $table->index(['student_id', 'created_at'], 'messages_student_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('messages', 'student_id')) {
            Schema::table('messages', function (Blueprint $table): void {
                $table->dropIndex('messages_student_created_idx');
                $table->dropConstrainedForeignId('student_id');
            });
        }
    }
};