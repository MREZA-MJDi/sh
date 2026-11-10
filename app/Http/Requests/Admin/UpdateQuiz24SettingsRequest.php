<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuiz24SettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermissionTo('integrations.manage') ?? false; }

    public function rules(): array
    {
        return [
            'account_name' => ['nullable', 'string', 'max:190'],
            'api_base_url' => ['nullable', 'url:https', 'max:1000'],
            'api_token' => ['nullable', 'string', 'max:5000'],
            'api_token_header' => ['nullable', 'string', 'regex:/^[A-Za-z0-9-]{1,100}$/'],
            'api_token_prefix' => ['nullable', 'string', 'max:100'],
            'exams_endpoint' => ['nullable', 'string', 'max:500', 'starts_with:/'],
            'exams_collection_path' => ['nullable', 'string', 'max:190'],
            'exam_id_path' => ['nullable', 'string', 'max:190'],
            'exam_title_path' => ['nullable', 'string', 'max:190'],
            'results_endpoint' => ['nullable', 'string', 'max:500', 'starts_with:/'],
            'results_collection_path' => ['nullable', 'string', 'max:190'],
            'result_student_identifier_path' => ['nullable', 'string', 'max:190'],
            'result_display_name_path' => ['nullable', 'string', 'max:190'],
            'result_score_path' => ['nullable', 'string', 'max:190'],
            'result_max_score_path' => ['nullable', 'string', 'max:190'],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
