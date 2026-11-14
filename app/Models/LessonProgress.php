<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = ['user_id', 'course_lesson_id', 'completed_at', 'last_position_seconds'];

    protected $casts = ['completed_at' => 'datetime'];
}
