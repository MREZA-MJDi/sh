<?php

namespace App\Services\Admin;

use App\Models\Course;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TeacherAccessService
{
    public const ACCESS_GROUPS = [
        'classes' => [
            'label' => 'مدیریت کلاس و کلاس آنلاین اسکای‌روم',
            'permissions' => ['classes.view.assigned', 'classes.manage.assigned', 'courses.view.assigned', 'students.view.assigned'],
        ],
        'exams' => [
            'label' => 'ساخت و مدیریت آزمون‌ها',
            'permissions' => ['exams.manage.assigned'],
        ],
        'course_content' => [
            'label' => 'مدیریت بخش‌ها و درس‌های دوره',
            'permissions' => ['courses.content.manage.assigned'],
        ],
        'videos' => [
            'label' => 'بارگذاری فیلم کلاس',
            'permissions' => ['videos.view.assigned', 'videos.manage.assigned'],
        ],
        'materials_homework' => [
            'label' => 'بارگذاری جزوه و تکلیف',
            'permissions' => ['resources.manage.assigned', 'homework.view.assigned', 'homework.manage.assigned'],
        ],
        'attendance' => [
            'label' => 'ثبت حضور و غیاب',
            'permissions' => ['attendance.view.assigned', 'attendance.manage.assigned'],
        ],
        'homework_review' => [
            'label' => 'بررسی و تصحیح تکالیف',
            'permissions' => ['homework.grade', 'homework.view.assigned'],
        ],
        'parent_messages' => [
            'label' => 'پیام و پاسخ‌گویی به اولیا',
            'permissions' => ['messages.view.assigned', 'messages.reply'],
        ],
    ];

    public function sync(User $teacher, array $accessGroups, array $courseIds, User $actor): void
    {
        if (! $teacher->hasRole('teacher')) {
            throw ValidationException::withMessages(['teacher' => 'این حساب، مدرس معتبر نیست.']);
        }

        $unknownGroups = array_diff($accessGroups, array_keys(self::ACCESS_GROUPS));
        if ($unknownGroups !== []) {
            throw ValidationException::withMessages(['teacher_access' => 'یکی از گروه‌های دسترسی معتبر نیست.']);
        }

        $permissionNames = collect($accessGroups)
            ->flatMap(fn (string $group) => self::ACCESS_GROUPS[$group]['permissions'])
            ->unique()
            ->values();

        $permissionIds = Permission::query()
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        if ($permissionIds->count() !== $permissionNames->count()) {
            throw ValidationException::withMessages([
                'teacher_access' => 'فهرست مجوزها کامل نیست؛ ابتدا نقش‌ها و مجوزهای سامانه را به‌روزرسانی کنید.',
            ]);
        }

        $courseIds = collect($courseIds)->map(fn ($id) => (int) $id)->unique()->values();
        if ($courseIds->isNotEmpty() && Course::query()->whereIn('id', $courseIds)->count() !== $courseIds->count()) {
            throw ValidationException::withMessages(['course_ids' => 'یکی از دوره‌های انتخاب‌شده معتبر نیست.']);
        }

        DB::transaction(function () use ($teacher, $permissionIds, $courseIds, $actor): void {
            $teacher->forceFill(['permissions_customized' => true])->save();

            $teacher->permissions()->syncWithPivotValues(
                $permissionIds->all(),
                ['granted_by' => $actor->id]
            );

            $previousAssignments = Course::query()->where('teacher_id', $teacher->id);
            if ($courseIds->isNotEmpty()) {
                $previousAssignments->whereNotIn('id', $courseIds->all());
            }
            $previousAssignments->update(['teacher_id' => null]);

            if ($courseIds->isNotEmpty()) {
                Course::query()->whereIn('id', $courseIds->all())->update(['teacher_id' => $teacher->id]);
            }
        });
    }

    public function selectedGroups(User $teacher): array
    {
        $teacher->loadMissing('roles.permissions', 'permissions');

        $permissionNames = $teacher->permissions_customized
            ? $teacher->permissions->pluck('name')
            : $teacher->roles->flatMap(fn ($role) => $role->permissions->pluck('name'));

        return collect(self::ACCESS_GROUPS)
            ->mapWithKeys(fn (array $group, string $key) => [
                $key => collect($group['permissions'])->intersect($permissionNames)->isNotEmpty(),
            ])
            ->all();
    }

    public function permissionLabels(User $teacher): array
    {
        $selected = $this->selectedGroups($teacher);

        return collect(self::ACCESS_GROUPS)
            ->filter(fn (array $group, string $key) => $selected[$key] ?? false)
            ->pluck('label')
            ->values()
            ->all();
    }
}
