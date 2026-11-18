<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    protected $fillable = ['classroom_id', 'title', 'session_type', 'starts_at', 'ends_at', 'meeting_provider', 'meeting_url', 'status', 'teacher_notes', 'created_by'];
    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function recordings(): HasMany { return $this->hasMany(SpotPlayerVideo::class, 'class_session_id'); }
    public function resources(): HasMany { return $this->hasMany(LearningResource::class, 'class_session_id'); }
    public function attendances(): HasMany { return $this->hasMany(Attendance::class, 'class_session_id'); }
}
