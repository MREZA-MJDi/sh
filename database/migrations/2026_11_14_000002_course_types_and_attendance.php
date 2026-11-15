<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $add = function (string $col, callable $def) {
            if (! Schema::hasColumn('courses', $col)) {
                Schema::table('courses', fn (Blueprint $t) => $def($t));
            }
        };
        $add('type', fn ($t) => $t->string('type', 20)->default('recorded')); // online_class | recorded | in_person
        $add('skyroom_room_id', fn ($t) => $t->string('skyroom_room_id')->nullable());
        $add('location', fn ($t) => $t->string('location')->nullable());

        if (! Schema::hasTable('class_sessions')) {
            Schema::create('class_sessions', function (Blueprint $t) {
                $t->id();
                $t->foreignId('course_id')->constrained()->cascadeOnDelete();
                $t->string('title');
                $t->dateTime('starts_at');
                $t->dateTime('ends_at');
                $t->timestamps();
                $t->index(['course_id', 'starts_at']);
            });
        }

        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $t) {
                $t->id();
                $t->foreignId('class_session_id')->constrained()->cascadeOnDelete();
                $t->foreignId('user_id')->constrained()->cascadeOnDelete();
                $t->string('status', 10); // present | late | absent | excused
                $t->unsignedSmallInteger('minutes_present')->nullable();
                $t->string('source', 20)->default('manual'); // manual | skyroom_import
                $t->timestamps();
                $t->unique(['class_session_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        // These tables may have been created by the earlier classroom-based migrations.
        // Drop only the alternate schema created by this migration itself.
        if (Schema::hasTable('attendances') && Schema::hasColumn('attendances', 'user_id') && ! Schema::hasColumn('attendances', 'student_id')) {
            Schema::dropIfExists('attendances');
        }
        if (Schema::hasTable('class_sessions') && Schema::hasColumn('class_sessions', 'course_id') && ! Schema::hasColumn('class_sessions', 'classroom_id')) {
            Schema::dropIfExists('class_sessions');
        }
    }
};
