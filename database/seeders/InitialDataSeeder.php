<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            InternalNotificationPermissionSeeder::class,
            SeoWorkspaceSeeder::class,
            AcademicGradeSeeder::class,
        ]);

        DB::transaction(function (): void {
            $teacherRole = Role::query()->where('name', 'teacher')->firstOrFail();
            $teacher = User::query()->firstOrCreate(
                ['email' => 'course-instructor@sheykhan.invalid'],
                [
                    'name' => 'مدرس دوره',
                    'password' => Hash::make(Str::random(48)),
                    'is_active' => true,
                ],
            );
            $teacher->roles()->syncWithoutDetaching([$teacherRole->id]);

            $course = Course::query()->firstOrCreate(
                ['slug' => 'study-skills'],
                [
                    'title' => 'مهارت‌های مطالعه و برنامه‌ریزی',
                    'description' => 'آموزش برنامه‌ریزی درسی، یادداشت‌برداری و مرور مطالب.',
                    'teacher_id' => $teacher->id,
                    'type' => 'free',
                    'price' => 0,
                    'delivery_type' => 'onsite',
                    'status' => 'published',
                    'learning_features' => [
                        'recorded_video' => true,
                        'resources' => true,
                    ],
                ],
            );

            if ($course->wasRecentlyCreated) {
                $course->classrooms()->create([
                    'name' => 'گروه اول مهارت‌های مطالعه',
                    'teacher_id' => $teacher->id,
                    'status' => 'active',
                ]);
            }
        });
    }
}
