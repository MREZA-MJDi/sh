<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Models\User;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['classes.manage', 'classes.manage.assigned']) ?? false;
    }

    public function rules(): array
    {
        $teacherView = app(ClassroomWorkspaceService::class)->isTeacher($this->user());

        return [
            'name' => ['required', 'string', 'max:180'],
            'course_id' => [$teacherView ? 'required' : 'nullable', 'integer', 'exists:courses,id'],
            'teacher_id' => [$teacherView ? 'prohibited' : 'required', 'integer', 'exists:users,id'],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'paused', 'archived'])],
            'schedule_days' => ['nullable', 'array'],
            'schedule_days.*' => [Rule::in(['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'])],
            'schedule_time' => ['nullable', 'date_format:H:i'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:300'],
            'student_ids' => ['nullable', 'array', 'max:300'],
            'student_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $workspace = app(ClassroomWorkspaceService::class);
            $teacherView = $workspace->isTeacher($this->user());

            if (! $teacherView && $this->filled('teacher_id') && ! User::query()
                ->whereKey($this->integer('teacher_id'))
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))
                ->exists()) {
                $validator->errors()->add('teacher_id', 'مدرس انتخاب‌شده فعال یا دارای نقش مدرس نیست.');
            }

            if ($teacherView && $this->filled('course_id') && ! Course::query()
                ->whereKey($this->integer('course_id'))
                ->where('teacher_id', $this->user()->id)
                ->exists()) {
                $validator->errors()->add('course_id', 'فقط دوره‌های تخصیص‌یافته به شما قابل انتخاب هستند.');
            }

            $ids = $this->input('student_ids', []);
            if (is_array($ids) && $ids !== []) {
                $count = User::query()->whereIn('id', $ids)->where('is_active', true)
                    ->whereHas('roles', fn ($query) => $query->where('name', 'student'))->count();

                if ($count !== count(array_unique($ids))) {
                    $validator->errors()->add('student_ids', 'فقط دانش‌آموزان فعال را می‌توان به کلاس افزود.');
                }
            }
        }];
    }
}
