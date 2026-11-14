<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
    protected $fillable = ['course_id', 'title', 'starts_at', 'ends_at'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime'];

    public function course(): BelongsTo { return $this->belongsTo(Course::class); }

    public function attendances(): HasMany { return $this->hasMany(Attendance::class); }
}
