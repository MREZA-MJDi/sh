<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GRADE_NAMES = [
        1 => 'اول', 2 => 'دوم', 3 => 'سوم', 4 => 'چهارم',
        5 => 'پنجم', 6 => 'ششم', 7 => 'هفتم', 8 => 'هشتم',
        9 => 'نهم', 10 => 'دهم', 11 => 'یازدهم', 12 => 'دوازدهم',
    ];

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

        // Backfill numeric, Persian-numeric and named legacy grades into
        // canonical records while preserving the original grade text.
        foreach (['courses', 'users'] as $tableName) {
            $legacyNames = DB::table($tableName)
                ->whereNotNull('grade')
                ->where('grade', '<>', '')
                ->distinct()
                ->pluck('grade');

            foreach ($legacyNames as $legacyName) {
                $normalized = $this->normalizeLegacyGradeName((string) $legacyName);
                if ($normalized === null) {
                    continue;
                }

                [$name, $level, $code] = $normalized;
                $gradeId = DB::table('academic_grades')->where('code', $code)->value('id');

                if (! $gradeId) {
                    $gradeId = DB::table('academic_grades')->where('name', $name)->value('id');
                }

                $attributes = [
                    'code' => $code,
                    'name' => $name,
                    'level' => $level,
                    'sort_order' => $level ?? 0,
                    'is_active' => true,
                    'updated_at' => now(),
                ];

                if ($gradeId) {
                    DB::table('academic_grades')->where('id', $gradeId)->update($attributes);
                } else {
                    $attributes['created_at'] = now();
                    $gradeId = DB::table('academic_grades')->insertGetId($attributes);
                }

                DB::table($tableName)
                    ->where('grade', $legacyName)
                    ->update(['academic_grade_id' => $gradeId]);
            }
        }
    }

    private function normalizeLegacyGradeName(string $value): ?array
    {
        $name = trim($value);
        $name = preg_replace('/^پایه\s+/u', '', $name) ?? $name;
        $name = strtr($name, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if ($name === '' || in_array($name, ['عمومی', 'سایر', 'بدون پایه'], true)) {
            return null;
        }

        if (preg_match('/^(?:0*[1-9]|1[0-2])$/', $name)) {
            $level = (int) $name;
            $name = self::GRADE_NAMES[$level];
        } else {
            $level = array_search($name, self::GRADE_NAMES, true);
            $level = $level === false ? null : (int) $level;
        }

        $code = $level !== null
            ? (string) $level
            : 'legacy-'.substr(sha1($name), 0, 12);

        return [$name, $level, $code];
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
