<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_grades')) {
            Schema::create('academic_grades', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 24)->unique();
                $table->string('name', 60)->unique();
                $table->unsignedTinyInteger('level')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('courses', 'academic_grade_id')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->foreignId('academic_grade_id')->nullable()
                    ->constrained('academic_grades')->nullOnDelete();
                $table->string('season', 20)->nullable();
            });
        } elseif (! Schema::hasColumn('courses', 'season')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->string('season', 20)->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'academic_grade_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('academic_grade_id')->nullable()
                    ->constrained('academic_grades')->nullOnDelete();
            });
        }

        Schema::table('courses', function (Blueprint $table): void {
            $table->index(
                ['academic_grade_id', 'season', 'status'],
                'courses_academic_grade_season_status_idx'
            );
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('academic_grade_id', 'users_academic_grade_idx');
        });

        // Backfill the normalized relation without deleting legacy grade text.
        $knownLevels = [
            'اول' => 1, 'دوم' => 2, 'سوم' => 3, 'چهارم' => 4, 'پنجم' => 5, 'ششم' => 6,
            'هفتم' => 7, 'هشتم' => 8, 'نهم' => 9, 'دهم' => 10, 'یازدهم' => 11, 'دوازدهم' => 12,
        ];

        foreach (['courses', 'users'] as $tableName) {
            $legacyNames = DB::table($tableName)
                ->whereNotNull('grade')
                ->where('grade', '<>', '')
                ->distinct()
                ->pluck('grade');

            foreach ($legacyNames as $legacyName) {
                $name = trim((string) $legacyName);
                $name = preg_replace('/^پایه\s+/u', '', $name) ?? $name;

                if ($name === '' || in_array($name, ['عمومی', 'سایر', 'بدون پایه'], true)) {
                    continue;
                }

                $level = $knownLevels[$name] ?? null;
                $code = $level !== null ? (string) $level : 'legacy-'.substr(sha1($name), 0, 12);
                $gradeId = DB::table('academic_grades')->where('name', $name)->value('id');

                if (! $gradeId) {
                    if (DB::table('academic_grades')->where('code', $code)->exists()) {
                        $code = 'legacy-'.substr(sha1($name.'|'.$code), 0, 12);
                    }

                    $gradeId = DB::table('academic_grades')->insertGetId([
                        'code' => $code,
                        'name' => $name,
                        'level' => $level,
                        'sort_order' => $level ?? 0,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table($tableName)
                    ->where('grade', $legacyName)
                    ->update(['academic_grade_id' => $gradeId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropIndex('courses_academic_grade_season_status_idx');
            if (Schema::hasColumn('courses', 'academic_grade_id')) {
                $table->dropConstrainedForeignId('academic_grade_id');
            }
            if (Schema::hasColumn('courses', 'season')) {
                $table->dropColumn('season');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_academic_grade_idx');
            if (Schema::hasColumn('users', 'academic_grade_id')) {
                $table->dropConstrainedForeignId('academic_grade_id');
            }
        });

        Schema::dropIfExists('academic_grades');
    }
};
