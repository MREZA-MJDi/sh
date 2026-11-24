<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_meetings', function (Blueprint $table): void {
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->unsignedBigInteger('guardian_id')->nullable()->change();
            $table->string('audience', 16)->default('individual');
        });

        // A classroom meeting is one event, with a snapshot of invited families.
        Schema::create('parent_meeting_attendees', function (Blueprint $table): void {
            $table->foreignId('parent_meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['parent_meeting_id', 'student_id', 'guardian_id'], 'parent_meeting_attendees_pk');
            $table->index(['guardian_id', 'student_id', 'parent_meeting_id'], 'parent_meeting_family_idx');
            $table->index(['student_id', 'parent_meeting_id'], 'parent_meeting_student_idx');
        });
    }

    public function down(): void
    {
        // Do not silently discard classroom-wide events when rolling back.
        if (DB::table('parent_meetings')->where('audience', 'classroom')->exists()) {
            throw new RuntimeException('Archive/export classroom meetings before rolling back this migration.');
        }

        Schema::dropIfExists('parent_meeting_attendees');
        Schema::table('parent_meetings', function (Blueprint $table): void {
            $table->dropColumn('audience');
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
            $table->unsignedBigInteger('guardian_id')->nullable(false)->change();
        });
    }
};
