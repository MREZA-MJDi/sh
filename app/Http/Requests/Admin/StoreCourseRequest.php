<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasRole('admin') && $this->user()->hasPermissionTo('courses.manage'); }

    protected function prepareForValidation(): void
    {
        $allowed = ['recorded_video', 'live_class', 'resources', 'assignments', 'exams', 'projects'];
        $legacyDefaults = [
            'recorded_video' => $this->input('delivery_type') === 'spotplayer',
            'live_class' => $this->input('delivery_type') === 'skyroom',
            'resources' => true,
            'assignments' => true,
            'exams' => true,
            'projects' => false,
        ];
        $features = $this->input('learning_features', []);

        $this->merge([
            'is_recommended' => $this->boolean('is_recommended'),
            'learning_features' => array_replace(
                $legacyDefaults,
                is_array($features) ? array_intersect_key($features, array_flip($allowed)) : []
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_cover_image' => ['nullable', 'boolean'],
            'subject' => ['nullable', 'string', 'max:100'],
            'grade' => ['nullable', 'string', 'max:50'],
            'academic_grade_id' => ['nullable', 'integer', 'exists:academic_grades,id'],
            'season' => ['nullable', Rule::in(array_keys(Course::SEASONS))],
            'academic_year' => ['nullable', 'integer', 'between:1300,1500'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['required', Rule::in(['free', 'paid'])],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'delivery_type' => ['required', Rule::in(['spotplayer', 'skyroom', 'onsite'])],
            'learning_features' => ['required', 'array'],
            'learning_features.recorded_video' => ['required', 'boolean'],
            'learning_features.live_class' => ['required', 'boolean'],
            'learning_features.resources' => ['required', 'boolean'],
            'learning_features.assignments' => ['required', 'boolean'],
            'learning_features.exams' => ['required', 'boolean'],
            'learning_features.projects' => ['required', 'boolean'],
            'spotplayer_course_id' => ['nullable', 'string', 'max:190'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_recommended' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان دوره را وارد کنید.',
            'title.max' => 'عنوان دوره حداکثر ۱۸۰ نویسه است.',
            'description.max' => 'معرفی دوره حداکثر ۱۰٬۰۰۰ نویسه است.',
            'cover_image.image' => 'یک تصویر معتبر انتخاب کنید.',
            'cover_image.mimes' => 'تصویر باید JPG، PNG یا WebP باشد.',
            'cover_image.max' => 'حجم تصویر باید حداکثر ۵ مگابایت باشد.',
            'price.integer' => 'قیمت را به تومان و بدون اعشار وارد کنید.',
            'price.min' => 'قیمت نمی‌تواند کمتر از صفر باشد.',
            'price.max' => 'قیمت نمی‌تواند بیشتر از یک میلیارد تومان باشد.',
            'type.required' => 'رایگان یا پولی بودن دوره را مشخص کنید.',
            'type.in' => 'نوع دوره را از گزینه‌های فرم انتخاب کنید.',
            'status.required' => 'وضعیت انتشار را مشخص کنید.',
            'status.in' => 'وضعیت انتشار را از گزینه‌های فرم انتخاب کنید.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $teacherId = $this->input('teacher_id');

            if ($teacherId && ! User::whereKey($teacherId)->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', 'teacher'))->exists()) {
                $validator->errors()->add('teacher_id', 'مدرس انتخاب‌شده فعال یا دارای نقش مدرس نیست.');
            }

            if ($this->input('type') === 'paid' && (int) $this->input('price', 0) < 1) {
                $validator->errors()->add('price', 'قیمت دوره پولی باید بیشتر از صفر باشد.');
            }
        }];
    }
}
