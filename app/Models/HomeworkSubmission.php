<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    protected $fillable = ['homework_id', 'student_id', 'answer', 'attachment_path', 'score', 'feedback', 'status', 'submitted_at', 'reviewed_by', 'reviewed_at'];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
    }

    public function homework(): BelongsTo
    {
        return $this->belongsTo(Homework::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
