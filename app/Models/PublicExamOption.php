<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicExamOption extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    protected $hidden = ['is_correct'];

    public function question()
    {
        return $this->belongsTo(PublicExamQuestion::class, 'public_exam_question_id');
    }
}
