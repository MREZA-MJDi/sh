<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicExamQuestion extends Model
{
    protected $guarded = [];

    public function exam()
    {
        return $this->belongsTo(PublicExam::class, 'public_exam_id');
    }

    public function options()
    {
        return $this->hasMany(PublicExamOption::class, 'public_exam_question_id')->orderBy('position');
    }
}
