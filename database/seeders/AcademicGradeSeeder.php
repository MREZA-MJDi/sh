<?php

namespace Database\Seeders;

use App\Models\AcademicGrade;
use Illuminate\Database\Seeder;

final class AcademicGradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['code' => '1', 'name' => 'اول', 'level' => 1],
            ['code' => '2', 'name' => 'دوم', 'level' => 2],
            ['code' => '3', 'name' => 'سوم', 'level' => 3],
            ['code' => '4', 'name' => 'چهارم', 'level' => 4],
            ['code' => '5', 'name' => 'پنجم', 'level' => 5],
            ['code' => '6', 'name' => 'ششم', 'level' => 6],
            ['code' => '7', 'name' => 'هفتم', 'level' => 7],
            ['code' => '8', 'name' => 'هشتم', 'level' => 8],
            ['code' => '9', 'name' => 'نهم', 'level' => 9],
            ['code' => '10', 'name' => 'دهم', 'level' => 10],
            ['code' => '11', 'name' => 'یازدهم', 'level' => 11],
            ['code' => '12', 'name' => 'دوازدهم', 'level' => 12],
        ];

        foreach ($grades as $grade) {
            AcademicGrade::query()->updateOrCreate(
                ['code' => $grade['code']],
                [
                    'name' => $grade['name'],
                    'level' => $grade['level'],
                    'sort_order' => $grade['level'],
                    'is_active' => true,
                ]
            );
        }
    }
}
