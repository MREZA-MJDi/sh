<?php

namespace App\Http\Requests\Admin;

use App\Models\Classroom;
use App\Models\Course;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSpotPlayerVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['integrations.manage', 'videos.manage.assigned']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'external_id' => ['nullable', 'string', 'max:190'],
            'embed_url' => ['nullable', 'url:https', 'max:2000'],
            'file' => ['required', 'file', 'mimes:mp4,mov,m4v,webm,mkv', 'max:1048576'],
            'status' => ['required', Rule::in(['draft', 'waiting_contract'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $workspace = app(ClassroomWorkspaceService::class);
            $course = Course::query()->find($this->integer('course_id'));

            if (! $course || $course->delivery_type !== 'spotplayer') {
                $validator->errors()->add('course_id', 'ویدیو باید به یک دوره با روش ارائه SpotPlayer متصل شود.');
                return;
            }

            if ($this->filled('classroom_id')
                && ! Classroom::query()
                    ->whereKey($this->integer('classroom_id'))
                    ->where('course_id', $course->id)
                    ->exists()) {
                $validator->errors()->add('classroom_id', 'کلاس انتخاب‌شده متعلق به این دوره نیست.');
            }

            if (! $workspace->isTeacher($this->user())) {
                return;
            }

            if (! $workspace->coursesQueryFor($this->user())->whereKey($course->id)->exists()) {
                $validator->errors()->add('course_id', 'این دوره به شما تخصیص داده نشده است.');
            }

            if ($this->filled('classroom_id')
                && ! $workspace->queryFor($this->user())->whereKey($this->integer('classroom_id'))->exists()) {
                $validator->errors()->add('classroom_id', 'این کلاس در فهرست کلاس‌های شما نیست.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'انتخاب فایل ویدیویی الزامی است.',
            'file.mimes' => 'فرمت فایل باید MP4، MOV، M4V، WebM یا MKV باشد.',
            'file.max' => 'حجم فایل ویدیو نباید از یک گیگابایت بیشتر باشد.',
        ];
    }
}
