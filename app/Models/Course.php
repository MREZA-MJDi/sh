<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'subject', 'grade', 'teacher_id', 'type', 'price', 'delivery_type', 'status'];

    protected function casts(): array
    {
        return ['price' => 'integer'];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LearningResource::class);
    }
}
