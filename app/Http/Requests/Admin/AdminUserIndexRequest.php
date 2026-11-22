<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdminUserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission([
            'users.manage',
            'students.view.assigned',
        ]) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'grade' => ['nullable', 'string', 'max:50'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }
}
