<?php

namespace App\Http\Requests\Admin;

use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['homework.manage.all', 'homework.manage.assigned']) ?? false;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'due_at' => ['nullable', 'date', 'after:now'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:20480'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $workspace = app(ClassroomWorkspaceService::class);

            if (! $workspace->queryFor($this->user())->whereKey($this->integer('classroom_id'))->exists()) {
                $validator->errors()->add('classroom_id', 'این کلاس در فهرست کلاس‌های مجاز شما نیست.');
            }
        }];
    }
}
