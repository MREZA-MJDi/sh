<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Attendance extends Model
{
    protected $fillable = [
        'classroom_id', 'class_session_id', 'student_id', 'session_date',
        'status', 'joined_at', 'left_at', 'note', 'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    protected function attendedMinutes(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->joined_at || ! $this->left_at) {
                return null;
            }

            $start = $this->joined_at->copy();
            $end = $this->left_at->copy();

            if ($this->classSession?->starts_at) {
                $start = $start->max($this->classSession->starts_at);
            }
            if ($this->classSession?->ends_at) {
                $end = $end->min($this->classSession->ends_at);
            }

            return $end->greaterThan($start) ? (int) $start->diffInMinutes($end) : 0;
        });
    }

    protected function lateMinutes(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->joined_at || ! $this->classSession?->starts_at
                || ! in_array($this->classSession->session_type, ['online', 'onsite', 'manual_attendance'], true)) {
                return null;
            }

            return max(0, (int) $this->classSession->starts_at->diffInMinutes($this->joined_at, false));
        });
    }

    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function classSession(): BelongsTo { return $this->belongsTo(ClassSession::class, 'class_session_id'); }
    public function student(): BelongsTo { return $this->belongsTo(User::class, 'student_id'); }
    public function marker(): BelongsTo { return $this->belongsTo(User::class, 'marked_by'); }
}
