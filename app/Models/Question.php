<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = ['exam_id', 'type', 'question', 'options', 'correct_answer', 'score', 'sort_order'];

    protected function casts(): array
    {
        return ['options' => 'array', 'score' => 'decimal:2', 'sort_order' => 'integer'];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class);
    }
}
