<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'classroom_id',
        'class_session_id',
        'student_id',
        'session_date',
        'scheduled_minutes',
        'attended_minutes',
        'status',
        'joined_at',
        'note',
        'marked_by',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'joined_at' => 'datetime',
            'scheduled_minutes' => 'integer',
            'attended_minutes' => 'integer',
        ];
    }

    public function scopeVisibleToStudent(Builder $query, User $student, Builder $classroomIds): Builder
    {
        return $query->where('student_id', $student->id)
            ->whereIn('classroom_id', $classroomIds)
            ->where('session_date', '<', now()->addDay()->toDateString())
            ->where(function (Builder $scope): void {
                $scope->whereNull('attendances.class_session_id')
                    ->orWhereHas('session', fn (Builder $session) => $session
                        ->where('status', '!=', 'cancelled')
                        ->where('starts_at', '<=', now())
                        ->whereColumn('class_sessions.classroom_id', 'attendances.classroom_id'));
            });
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function marker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'class_session_id');
    }

    public function classSession(): BelongsTo
    {
        return $this->session();
    }

    public function getScheduledMinutesAttribute($value): ?int
    {
        if ($value !== null) {
            return (int) $value;
        }

        $session = $this->session;
        if (! $session?->starts_at || ! $session?->ends_at) {
            return null;
        }

        $seconds = $session->ends_at->getTimestamp() - $session->starts_at->getTimestamp();

        return $seconds > 0 ? intdiv($seconds, 60) : null;
    }

    public function getLateMinutesAttribute(): ?int
    {
        if (! $this->joined_at || ! $this->session?->starts_at) {
            return null;
        }

        $seconds = $this->joined_at->getTimestamp() - $this->session->starts_at->getTimestamp();

        return $seconds > 0 ? (int) ceil($seconds / 60) : 0;
    }
}