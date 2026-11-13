<?php

namespace App\Http\Requests\Guardian;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class StoreParentMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('meetings.create') ?? false;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $linkedStudent = $this->user()?->children()
                ->whereKey($this->integer('student_id'))
                ->where('users.is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', 'student'))
                ->exists();

            if (! $linkedStudent) {
                $validator->errors()->add('student_id', 'این دانش‌آموز به حساب شما متصل نیست.');
            }
        }];
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'title' => ['required', 'string', 'max:180'],
            'agenda' => ['nullable', 'string', 'max:5000'],
            'meeting_type' => ['required', Rule::in(['in_person', 'phone', 'online'])],
            'proposed_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}