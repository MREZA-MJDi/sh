<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    protected $fillable = [
        'exam_id', 'student_id', 'attempt_number', 'started_at', 'submitted_at',
        'score', 'max_score', 'status', 'feedback', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
        ];
    }

    public function exam(): BelongsTo { return $this->belongsTo(Exam::class); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function answers(): HasMany { return $this->hasMany(ExamAnswer::class); }
}
