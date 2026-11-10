<?php

namespace App\\Http\\Requests\\Admin;

use Illuminate\\Foundation\\Http\\FormRequest;
use Illuminate\\Validation\\Rule;
use Illuminate\\Validation\\Validator;

class UpdateLearningResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('resources.manage') ?? false;
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
        }];
    }
}
