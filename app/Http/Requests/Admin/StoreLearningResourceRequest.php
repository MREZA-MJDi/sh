<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['resources.manage', 'resources.manage.assigned']) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'kind' => ['required', Rule::in(['pdf', 'book', 'other'])],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'is_free' => ['sometimes', 'boolean'],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'visibility' => ['required', Rule::in(['public', 'authenticated', 'enrolled'])],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:30720'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $price = (int) $this->input('price', 0);
            if ($this->boolean('is_free') && $price !== 0) {
                $validator->errors()->add('price', 'برای منبع رایگان، قیمت باید صفر باشد.');
            }
            if (! $this->boolean('is_free') && $price < 1) {
                $validator->errors()->add('price', 'برای محصول پولی، قیمت باید بیشتر از صفر باشد.');
            }

            if ($this->filled('classroom_id') && $this->filled('course_id')
                && ! Classroom::query()
                    ->whereKey($this->integer('classroom_id'))
                    ->where('course_id', $this->integer('course_id'))
                    ->exists()) {
                $validator->errors()->add('classroom_id', 'کلاس انتخاب‌شده متعلق به این دوره نیست.');
            }

            $workspace = app(ClassroomWorkspaceService::class);
            if (! $workspace->isTeacher($this->user())) {
                return;
            }

            if (! $this->filled('course_id') && ! $this->filled('classroom_id')) {
                $validator->errors()->add('course_id', 'برای بارگذاری مدرس، منبع باید به دوره یا کلاس خودتان متصل باشد.');
            }

            if ($this->filled('course_id') && ! $workspace->coursesQueryFor($this->user())
                ->whereKey($this->integer('course_id'))->exists()) {
                $validator->errors()->add('course_id', 'این دوره به شما تخصیص داده نشده است.');
            }

            if ($this->filled('classroom_id') && ! $workspace->queryFor($this->user())
                ->whereKey($this->integer('classroom_id'))->exists()) {
                $validator->errors()->add('classroom_id', 'این کلاس در فهرست کلاس‌های شما نیست.');
            }

            if ($this->input('visibility') === 'public') {
                $validator->errors()->add('visibility', 'مدرس فقط می‌تواند منبع را برای کاربران مجاز یا اعضای ثبت‌نام‌شده منتشر کند.');
            }
        }];
    }
}
