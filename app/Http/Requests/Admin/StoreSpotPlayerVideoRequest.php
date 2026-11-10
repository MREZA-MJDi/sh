<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSpotPlayerVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('integrations.manage') ?? false;
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
            $courseId = $this->integer('course_id');
            if ($courseId && ! Course::whereKey($courseId)->where('delivery_type', 'spotplayer')->exists()) {
                $validator->errors()->add('course_id', 'ویدیو باید به یک دوره با روش ارائه SpotPlayer متصل شود.');
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
