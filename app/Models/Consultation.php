<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    protected $fillable = [
        'requester_id', 'student_id', 'teacher_id', 'handled_by',
        'type', 'subject', 'contact_phone', 'desired_at', 'scheduled_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return ['desired_at' => 'datetime', 'scheduled_at' => 'datetime'];
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ConsultationReply::class)->with('sender')->oldest();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
