<?php

namespace App\Services\Admin;

use App\Models\Classroom;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UserManagementService
{
    public function __construct(
        private readonly TeacherAccessService $teacherAccess,
        private readonly CourseEnrollmentService $enrollments,
    ) {}

    public function create(array $data, string $role, User $actor): User
    {
        if ($role === 'seo' && ! $actor->hasRole('seo')) {
            throw ValidationException::withMessages(['role' => 'مدیریت اجازه تخصیص یا تغییر دسترسی سئو را ندارد.']);
        }

        return DB::transaction(function () use ($data, $role, $actor): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'external_student_id' => $role === 'student' ? ($data['external_student_id'] ?? null) : null,
                'password' => $data['password'],
                'grade' => $role === 'student' ? ($data['grade'] ?? null) : null,
                'subject' => in_array($role, ['teacher'], true) ? ($data['subject'] ?? null) : null,
                'is_active' => $role === 'teacher' ? (($data['status'] ?? 'active') === 'active') : true,
                'permissions_customized' => false,
            ]);

            $roleModel = Role::query()->where('name', $role)->firstOrFail();
            $user->roles()->sync([$roleModel->id]);

            if ($role === 'student' && ! empty($data['classroom_ids'])) {
                $user->enrolledClasses()->syncWithPivotValues(
                    $data['classroom_ids'],
                    ['assigned_by' => $actor->id]
                );

                foreach ($user->enrolledClasses()->with('course')->get() as $classroom) {
                    $this->enrollments->syncClassroomRoster($classroom);
                }
            }

            if ($role === 'parent' && ! empty($data['child_ids'])) {
                $user->children()->sync($data['child_ids']);
            }

            if ($role === 'teacher') {
                $this->teacherAccess->sync(
                    $user,
                    $data['teacher_access'] ?? [],
                    $data['course_ids'] ?? [],
                    $actor
                );
            }

            return $user->refresh();
        });
    }

    public function updateTeacher(User $teacher, array $data, User $actor): User
    {
        if (! $teacher->hasRole('teacher') || $teacher->hasRole('admin')) {
            throw ValidationException::withMessages(['teacher' => 'حساب انتخاب‌شده مدرس قابل ویرایش نیست.']);
        }

        return DB::transaction(function () use ($teacher, $data, $actor): User {
            $teacher->fill([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'subject' => $data['subject'],
                'is_active' => $data['status'] === 'active',
            ]);

            if (filled($data['password'] ?? null)) {
                $teacher->password = $data['password'];
            }

            $teacher->save();

            $this->teacherAccess->sync(
                $teacher,
                $data['teacher_access'] ?? [],
                $data['course_ids'] ?? [],
                $actor
            );

            return $teacher->refresh();
        });
    }
}
