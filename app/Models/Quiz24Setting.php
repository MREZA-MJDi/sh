<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quiz24Setting extends Model
{
    protected $fillable = [
        'account_name',
        'api_base_url',
        'api_token',
        'is_enabled',
        'api_token_header',
        'api_token_prefix',
        'exams_endpoint',
        'exams_collection_path',
        'exam_id_path',
        'exam_title_path',
        'results_endpoint',
        'results_collection_path',
        'result_student_identifier_path',
        'result_display_name_path',
        'result_score_path',
        'result_max_score_path',
    ];

    protected function casts(): array
    {
        return [
            'api_token' => 'encrypted',
            'is_enabled' => 'boolean',
        ];
    }
}
