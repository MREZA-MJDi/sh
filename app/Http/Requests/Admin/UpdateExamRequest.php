<?php
namespace App\Http\Requests\Admin;
use App\Services\Admin\ClassroomWorkspaceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasAnyPermission(['exams.manage', 'exams.manage.assigned']) ?? false; }
    public function rules(): array
    {
        $exam = $this->route('exam');
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:10000'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'provider' => ['required', Rule::in(['internal', 'quiz24'])],
            'external_id' => ['nullable', 'string', 'max:190', 'prohibited_unless:provider,quiz24', Rule::unique('exams', 'external_id')->where('provider', 'quiz24')->ignore($exam?->id)],
            'starts_at' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'published', 'closed'])],
        ];
    }
    public function after(): array
    {
        return [function (Validator $validator): void {
            $workspace = app(ClassroomWorkspaceService::class);
            $user = $this->user();
            if (! $workspace->isTeacher($user)) return;
            $classroomId = $this->integer('classroom_id');
            $courseId = $this->integer('course_id');
            if (! $classroomId && ! $courseId) {
                $validator->errors()->add('classroom_id', 'آزمون مدرس باید به کلاس یا دوره تخصیص‌یافته متصل باشد.');
                return;
            }
            if ($classroomId && ! $workspace->queryFor($user)->whereKey($classroomId)->exists()) {
                $validator->errors()->add('classroom_id', 'این کلاس در فهرست کلاس‌های شما نیست.');
            }
            if ($courseId && ! $workspace->coursesQueryFor($user)->whereKey($courseId)->exists()) {
                $validator->errors()->add('course_id', 'این دوره به شما تخصیص داده نشده است.');
            }
        }];
    }
}
