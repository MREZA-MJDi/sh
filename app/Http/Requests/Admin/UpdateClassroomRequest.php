<?php

namespace App\Http\Requests\Admin;

final class UpdateClassroomRequest extends StoreClassroomRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'student_ids_present' => ['sometimes', 'boolean'],
        ];
    }
}
