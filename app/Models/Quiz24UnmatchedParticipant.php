<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quiz24UnmatchedParticipant extends Model
{
    protected $fillable = [
        'exam_id',
        'external_identifier',
        'display_name',
        'score',
        'max_score',
        'status',
        'resolved_user_id',
        'source_data',
        'first_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'source_data' => 'encrypted:array',
            'first_seen_at' => 'datetime',
        ];
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function resolvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_user_id');
    }
}
