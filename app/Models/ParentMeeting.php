<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentMeeting extends Model
{
    protected $fillable = [
        'guardian_id', 'student_id', 'classroom_id', 'created_by', 'title', 'agenda',
        'meeting_type', 'meeting_url', 'proposed_at', 'scheduled_at', 'ends_at', 'status',
        'admin_notes', 'guardian_responded_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'ends_at' => 'datetime',
            'meeting_url' => 'encrypted',
            'guardian_responded_at' => 'datetime',
        ];
    }

    public function guardian(): BelongsTo { return $this->belongsTo(User::class, 'guardian_id'); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}