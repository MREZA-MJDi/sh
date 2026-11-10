<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\Classroom;

class StoreHomeworkRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasAnyPermission(['homework.manage.all', 'homework.manage.assigned']) ?? false; }

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
            $classroom = Classroom::find($this->integer('classroom_id'));
            if (! $classroom) return;

            if ($this->user()?->hasRole('teacher') && ! $this->user()?->hasRole('admin')
                && $classroom->teacher_id !== $this->user()->id) {
                $validator->errors()->add('classroom_id', 'این کلاس در فهرست کلاس‌های شما نیست.');
            }
        }];
    }
}
