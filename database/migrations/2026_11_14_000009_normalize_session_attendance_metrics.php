<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('attendances') && Schema::hasIndex('attendances', 'attendance_unique_session')) {
            // The legacy composite unique index starts with classroom_id and may be
            // the only index InnoDB can use for the classroom_id foreign key.
            // Install a dedicated FK-supporting index before removing that unique.
            if (! Schema::hasIndex('attendances', ['classroom_id'])) {
                Schema::table('attendances', function (Blueprint $table): void {
                    $table->index('classroom_id', 'attendances_classroom_id_index');
                });
            }

            Schema::table('attendances', function (Blueprint $table): void {
                $table->dropUnique('attendance_unique_session');
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table): void {
                if (! Schema::hasColumn('attendances', 'scheduled_minutes')) {
                    $table->unsignedSmallInteger('scheduled_minutes')->nullable()->after('joined_at');
                }
                if (! Schema::hasColumn('attendances', 'attended_minutes')) {
                    $table->unsignedSmallInteger('attended_minutes')->nullable()->after('scheduled_minutes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table): void {
                $columns = array_values(array_filter(
                    ['scheduled_minutes', 'attended_minutes'],
                    fn (string $column): bool => Schema::hasColumn('attendances', $column)
                ));
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });

            $hasDuplicateClassDateRows = DB::table('attendances')
                ->select('classroom_id', 'student_id', 'session_date')
                ->groupBy('classroom_id', 'student_id', 'session_date')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (! $hasDuplicateClassDateRows && ! Schema::hasIndex('attendances', 'attendance_unique_session')) {
                Schema::table('attendances', function (Blueprint $table): void {
                    $table->unique(['classroom_id', 'student_id', 'session_date'], 'attendance_unique_session');
                });
            }
        }
    }
};
