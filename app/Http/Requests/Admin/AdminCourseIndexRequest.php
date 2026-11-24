<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminCourseIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission([
            'courses.manage',
            'courses.view.assigned',
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'grade' => ['nullable', 'string', 'max:50'],
            'season' => ['nullable', Rule::in(array_keys(Course::SEASONS))],
            'academic_year' => ['nullable', 'integer', 'between:1300,1500'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
