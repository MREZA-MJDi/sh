<?php

namespace App\Http\Requests\Admin;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RequestSpotPlayerLicenseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('integrations.manage') ?? false; }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $studentId = $this->integer('student_id');
            $courseId = $this->integer('course_id');

            if ($studentId && ! User::whereKey($studentId)->where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('name', 'student'))->exists()) {
                $validator->errors()->add('student_id', 'دانش‌آموز انتخاب‌شده فعال نیست یا نقش دانش‌آموز ندارد.');
            }

            if ($courseId && ! Course::whereKey($courseId)->where('delivery_type', 'spotplayer')
                ->where('status', 'published')->whereNotNull('spotplayer_course_id')->exists()) {
                $validator->errors()->add('course_id', 'دوره باید منتشرشده و دارای شناسه اتصال اسپات پلیر باشد.');
            }
        }];
    }
}
